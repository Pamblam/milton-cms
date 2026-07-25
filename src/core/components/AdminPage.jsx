
/**
 * AdminPage.jsx 
 * The wrapper component for all the administration pages, includes sidebar, breadcrumbs
 */

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCaretRight, faGear, faFileCirclePlus, faFileLines, faUsers, faImages } from '@fortawesome/free-solid-svg-icons';

import {useNavHelper} from '#hooks/useNavHelper';
import { Breadcrumbs } from '#components/Breadcrumbs';
import { AuthPage } from '#components/AuthPage';

export function AdminPage({children, crumbs}){
	const navigate = useNavHelper();

	let activePage = crumbs.at(-1);
	let adminLinks = [
		{title: "All Posts", path: "/admin",  icon:faFileLines},
		{title: "New Post", path: "/new_post", icon:faFileCirclePlus},
		{title: "Media", path: "/media", icon:faImages},
		{title: "Edit Users", path: "/users", icon:faUsers},
		{title: "Settings", path: "/settings", icon:faGear}
	];

	return (<AuthPage>
		<div className='row'>
			<div className='col-md-3'>
				<nav className="nav flex-column">
					{adminLinks.map((link, key)=>{
						if(link.path === activePage.path){
							return (<a key={key} className="btn btn-primary mb-2" aria-current="page" href="#" onClick={e=>e.preventDefault()}><FontAwesomeIcon icon={faCaretRight} /> {link.title}</a>);
						}else{
							let icon = link.icon ? <FontAwesomeIcon icon={link.icon} /> : <>t</>;
							return (<a key={key} className="btn btn-primary mb-2" href="#" onClick={e=>navigate(e, link.path)}>{icon} {link.title}</a>);
						}
					})}
				</nav>
			</div>
			<div className='col-md-9'>
				<Breadcrumbs crumbs={crumbs} />

				{children}
			</div>
		</div>
	</AuthPage>);
}