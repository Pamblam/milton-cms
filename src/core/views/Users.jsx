/**
 * Users.jsx
 * The admin page for viewing and editing user accounts.
 * Admins can see and edit every account; everyone else sees only their own.
 */

import {useContext, useState, useEffect, useRef} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPenToSquare, faCircleCheck, faXmark, faUserShield } from '@fortawesome/free-solid-svg-icons';

import { AdminPage } from '#components/AdminPage';
import { APIRequest } from '#modules/APIRequest';
import { AppStateContext } from '#App';

export function Users(){
	const {userSession} = useContext(AppStateContext);
	const [loading, setLoading] = useState(true);
	const [users, setUsers] = useState([]);
	const [viewerIsAdmin, setViewerIsAdmin] = useState(false);
	const [editing, setEditing] = useState(null); // the user object being edited
	const [errorMessage, setErrorMessage] = useState();
	const [successMessage, setSuccessMessage] = useState();

	const username_ref = useRef();
	const display_name_ref = useRef();
	const password_ref = useRef();
	const is_admin_ref = useRef();

	const loadUsers = async () => {
		setLoading(true);
		let res = await new APIRequest('User', userSession).get();
		setLoading(false);
		if(res.has_error){
			setErrorMessage(res.message);
			return;
		}
		setUsers(res.data.users || []);
		setViewerIsAdmin(!!res.data.viewer_is_admin);
	};

	useEffect(()=>{ loadUsers(); }, []);

	const startEdit = (user) => {
		setErrorMessage(undefined);
		setSuccessMessage(undefined);
		setEditing(user);
	};

	const onSave = async (e) => {
		e.preventDefault();
		const params = {
			display_name: display_name_ref.current.value.trim(),
			username: username_ref.current.value.trim()
		};
		if(password_ref.current.value) params.password = password_ref.current.value;
		if(viewerIsAdmin && is_admin_ref.current) params.is_admin = is_admin_ref.current.checked ? '1' : '0';

		let res = await new APIRequest(`User/${editing.id}`, userSession).patch(params);
		if(res.has_error){
			setErrorMessage(res.message);
			return;
		}
		setEditing(null);
		setSuccessMessage(`Saved changes to ${res.data.user.username}.`);
		setErrorMessage(undefined);
		await loadUsers();
	};

	let crumbs = [{title:"Home", path:"/"},{title:"Admin",path:'/admin'},{title:"Edit Users",path:'/users'}];

	return (<AdminPage crumbs={crumbs}>

		{successMessage && (<div className='alert alert-success alert-dismissible'>{successMessage}<button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setSuccessMessage(undefined);}}></button></div>)}
		{errorMessage && (<div className='alert alert-danger alert-dismissible'>{errorMessage}<button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setErrorMessage(undefined);}}></button></div>)}

		{loading ? <p>Loading...</p> : (editing ? (
			<form onSubmit={onSave}>
				<h5 className="mb-3">Editing <b>{editing.username}</b></h5>

				<div className="mb-3">
					<label className="form-label">Username</label>
					<input data-lpignore="true" type="text" className="form-control" defaultValue={editing.username} ref={username_ref} />
					<div className="form-text">Letters, numbers and underscores only; 4-15 characters.</div>
				</div>

				<div className="mb-3">
					<label className="form-label">Display Name</label>
					<input data-lpignore="true" type="text" className="form-control" defaultValue={editing.display_name} ref={display_name_ref} />
				</div>

				<div className="mb-3">
					<label className="form-label">New Password</label>
					<input data-lpignore="true" type="password" className="form-control" placeholder="Leave blank to keep current password" ref={password_ref} />
					<div className="form-text">At least 8 characters. Leave blank to keep the existing password.</div>
				</div>

				{viewerIsAdmin && (
					<div className="mb-3 form-check">
						<input className="form-check-input" type="checkbox" id="user_is_admin" defaultChecked={editing.is_admin} ref={is_admin_ref} />
						<label className="form-check-label" htmlFor="user_is_admin">
							<FontAwesomeIcon icon={faUserShield} /> Administrator
						</label>
						<div className="form-text">Admins can see and edit every user account.</div>
					</div>
				)}

				<button className="btn btn-primary me-1" type="submit"><FontAwesomeIcon icon={faCircleCheck} /> Save</button>
				<button className="btn btn-secondary" type="button" onClick={()=>setEditing(null)}><FontAwesomeIcon icon={faXmark} /> Cancel</button>
			</form>
		) : (
			<table className="table table-striped align-middle">
				<thead>
					<tr>
						<th>Username</th>
						<th>Display Name</th>
						<th>Admin</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					{users.map(user=>(
						<tr key={user.id}>
							<td><b>{user.username}</b></td>
							<td>{user.display_name}</td>
							<td>{user.is_admin
								? <span style={{color:'green'}} title="Administrator"><FontAwesomeIcon icon={faUserShield} /></span>
								: <span style={{color:'#bbb'}}><FontAwesomeIcon icon={faXmark} /></span>}</td>
							<td>
								<a href="#" onClick={e=>{e.preventDefault(); startEdit(user);}}><FontAwesomeIcon icon={faPenToSquare} /> Edit</a>
							</td>
						</tr>
					))}
				</tbody>
			</table>
		))}
	</AdminPage>);
}
