<?php

/**
 * LoginThrottle
 * Simple IP-based brute-force protection for the password login endpoint.
 *
 * Failed attempts are recorded in the `login_attempts` table. Once an IP has
 * accumulated MAX_ATTEMPTS failures within a rolling WINDOW_SECONDS window it
 * is refused (HTTP 429) until the oldest attempt in that window ages out. A
 * successful login clears the IP's record so a legitimate user is never held
 * back by their own earlier typos.
 */
class LoginThrottle{

	const MAX_ATTEMPTS = 5;
	const WINDOW_SECONDS = 900; // 15 minutes

	/** Whether this IP has hit the failure limit within the current window. */
	public static function tooManyAttempts($pdo, $ip){
		$since = time() - self::WINDOW_SECONDS;
		$stmt = $pdo->prepare("SELECT COUNT(1) FROM `login_attempts` WHERE `ip` = ? AND `attempt_time` >= ?");
		$stmt->execute([$ip, $since]);
		return intval($stmt->fetchColumn()) >= self::MAX_ATTEMPTS;
	}

	/** Seconds until this IP is allowed to try again (0 if not throttled). */
	public static function retryAfter($pdo, $ip){
		$since = time() - self::WINDOW_SECONDS;
		$stmt = $pdo->prepare("SELECT MIN(`attempt_time`) FROM `login_attempts` WHERE `ip` = ? AND `attempt_time` >= ?");
		$stmt->execute([$ip, $since]);
		$oldest = $stmt->fetchColumn();
		if($oldest === false || $oldest === null) return 0;
		return max(0, (intval($oldest) + self::WINDOW_SECONDS) - time());
	}

	/** Record a failed login attempt for this IP. */
	public static function record($pdo, $ip, $username){
		$stmt = $pdo->prepare("INSERT INTO `login_attempts` (`ip`, `username`, `attempt_time`) VALUES (?, ?, ?)");
		$stmt->execute([$ip, (string) $username, time()]);
		// Opportunistically prune rows that are well outside any window so the
		// table can't grow without bound on a busy or targeted site.
		$pdo->prepare("DELETE FROM `login_attempts` WHERE `attempt_time` < ?")
			->execute([time() - (self::WINDOW_SECONDS * 4)]);
	}

	/** Clear an IP's failures after a successful login. */
	public static function clear($pdo, $ip){
		$pdo->prepare("DELETE FROM `login_attempts` WHERE `ip` = ?")->execute([$ip]);
	}

}
