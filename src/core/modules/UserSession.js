/**
 * UserSession.js
 * Class to handle the user's session on the client side.
 */

import { APIRequest } from "#modules/APIRequest";

class UserSession{

	constructor(){
		// last time the session was authenticated by the server
		this.server_auth_time = null;

		// Set Session properties
		this.props = [
			'id',
			'last_checked',
			'username',
			'display_name',
			'token',
			'token_issued'
		];

		this.validateServerPromise = null;

		// Holds an in-flight token refresh so concurrent requests share one
		// rotation instead of each racing to burn the same single-use token.
		this.updateTokenPromise = null;
	}

	// Whether the current token should be proactively refreshed. The server
	// session is valid for 6 hours from when the token was issued; we refresh
	// after 5 to leave a margin, so during normal use the token stays stable
	// and concurrent requests never carry an already-rotated (deleted) token.
	// NOTE: intentionally synchronous — it is used directly in a boolean test.
	isExpired(){
		if(!this.get('token')) return true;
		let issued = this.get('token_issued');
		if(!issued) return true;
		let now = new Date().getTime();
		return issued <= now - (1000 * 60 * 60 * 5);
	}

	async logout(){
		let res = await new APIRequest('Session', this).delete();
		this.props.forEach(key=>{
			this.set(key, null);
		});
	}

	async updateToken(){
		// Serialize refreshes: if one is already in flight, everyone waits on
		// it rather than firing their own rotation and racing over the token.
		if(this.updateTokenPromise) return this.updateTokenPromise;
		this.updateTokenPromise = (async ()=>{
			let res = await new APIRequest('Session/updateToken', this).patch();
			if(!res.has_error && res.data.User && res.data.User.id){
				this.set('display_name', res.data.User.display_name);
				this.set('username', res.data.User.username);
				this.set('id', res.data.User.id);
				return true;
			}
			return false;
		})();
		try {
			return await this.updateTokenPromise;
		} finally {
			this.updateTokenPromise = null;
		}
	}

	// Log in to start a session
	async login(username, password){
		let res = await new APIRequest('Session', this).post({username, password});
		if(!res.has_error && res.data.User && res.data.User.id){
			this.set('display_name', res.data.User.display_name);
			this.set('username', res.data.User.username);
			this.set('id', res.data.User.id);
			return true;
		}
		return false;
	}

	// Check if user is currently logged in, without asking the server
	isLoggedIn(){
		return this.get('last_checked') && this.get('id');
	}

	// If there is a current session older than 5 minutes, validate it with the server
	validateSession(){
		if(this.validateServerPromise) return this.validateServerPromise;
		this.validateServerPromise = new Promise(async done=>{
			if(this.get('token')){
				let now = new Date().getTime();
				let five_minutes_ago = now - (1000 * 60 * 5);	
				if(this.get('last_checked') && (this.get('last_checked') > five_minutes_ago)){
					done(true);
				}else{
					let res = await new APIRequest('Session', this).get();
					if(!res.has_error && res.data.User && res.data.User.id){
						this.set('display_name', res.data.User.display_name);
						this.set('username', res.data.User.username);
						this.set('id', res.data.User.id);
						this.set('last_checked', now);
						done(true);
					}else{
						done(false);
					}
				}
			}else{
				done(false);
			}
			
		}).then((res)=>{
			this.validateServerPromise = null;
			return res;
		});
		return this.validateServerPromise;
	}

	set(prop, val){
		if(this.props.includes(prop)){
			if(val === null){
				localStorage.removeItem(`session.${prop}`);
			}else{
				localStorage.setItem(`session.${prop}`, val);
			}
			return true;
		}
		return false;
	}

	get(prop){
		let val = localStorage.getItem(`session.${prop}`);
		if(!val) return;
		return ['id', 'last_checked', 'token_issued'].includes(prop) ? +val : val;
	}
}

export const userSession = new UserSession();