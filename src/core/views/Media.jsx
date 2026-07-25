/**
 * Media.jsx
 * The admin page that catalogs uploaded images. Images can be uploaded here
 * (same endpoint the New Post page uses) and deleted.
 */

import {useContext, useState, useEffect, useRef, useCallback} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrash, faUpload, faCopy } from '@fortawesome/free-solid-svg-icons';
import server_config from '#config/server';

import { AdminPage } from '#components/AdminPage';
import { APIRequest } from '#modules/APIRequest';
import { FI } from '#modules/FI';
import { AppStateContext } from '#App';

export function Media(){
	const {userSession} = useContext(AppStateContext);
	const [loading, setLoading] = useState(true);
	const [uploading, setUploading] = useState(false);
	const [images, setImages] = useState([]);
	const [errorMessage, setErrorMessage] = useState();
	const [successMessage, setSuccessMessage] = useState();

	const fi_instance_ref = useRef();
	const upload_btn_ref = useRef();

	const loadImages = async () => {
		setLoading(true);
		let res = await new APIRequest('Image', userSession).get();
		setLoading(false);
		if(res.has_error){
			setErrorMessage(res.message);
			return;
		}
		setImages(res.data.images || []);
	};

	useEffect(()=>{ loadImages(); }, []);

	const confirmAndDelete = async (image) => {
		if(!confirm(`Delete "${image.orig_name}"? This cannot be undone.`)) return;
		let res = await new APIRequest(`Image/${image.id}`, userSession).delete();
		if(res.has_error){
			setErrorMessage(res.message);
			return;
		}
		setSuccessMessage(`Deleted ${image.orig_name}.`);
		setErrorMessage(undefined);
		await loadImages();
	};

	const copyMarkdown = (image) => {
		navigator.clipboard.writeText(`![](${encodeURI(image.path)})`);
		setSuccessMessage(`Copied markdown for ${image.orig_name}.`);
	};

	const set_upload_btn_ref = useCallback(node=>{
		if (!node) return;
		if (upload_btn_ref.current) fi_instance_ref.current.destroy();
		upload_btn_ref.current = node;

		fi_instance_ref.current = new FI({
			button: node,
			accept: ["png", "jpg", "jpeg", "gif"],
			multi: true
		});

		fi_instance_ref.current.register_callback(async function(){
			let files = fi_instance_ref.current.get_files();
			if(!files || !files.length) return;
			fi_instance_ref.current.clear_files();

			setUploading(true);
			setErrorMessage(undefined);
			let failed = 0;
			for(let file of files){
				let res = {};
				try{
					res = await new APIRequest('Image', userSession).post({img: file});
				}catch(e){
					res = {has_error: true};
				}
				if(res.has_error) failed++;
			}
			setUploading(false);

			if(failed) setErrorMessage(`${failed} file(s) failed to upload. Check the size and type restrictions.`);
			else setSuccessMessage('Upload complete.');
			await loadImages();
		});
	});

	let crumbs = [{title:"Home", path:"/"},{title:"Admin",path:'/admin'},{title:"Media",path:'/media'}];

	return (<AdminPage crumbs={crumbs}>

		{successMessage && (<div className='alert alert-success alert-dismissible'>{successMessage}<button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setSuccessMessage(undefined);}}></button></div>)}
		{errorMessage && (<div className='alert alert-danger alert-dismissible'>{errorMessage}<button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setErrorMessage(undefined);}}></button></div>)}

		<button ref={set_upload_btn_ref} type="button" className="btn btn-primary mb-3">
			<FontAwesomeIcon icon={faUpload} /> Upload Images
		</button>
		{uploading && <span className="ms-2">Uploading…</span>}

		{loading ? <p>Loading...</p> : (
			images.length === 0 ? <p>No images have been uploaded yet.</p> : (
				<div className="row">
					{images.map(image=>(
						<div className="col-6 col-md-3 mb-4" key={image.id}>
							<div className="card h-100">
								<img src={server_config.base_url + image.path} className="card-img-top" style={{objectFit:'cover', height:'140px'}} alt={image.orig_name} />
								<div className="card-body p-2">
									<div className="text-truncate" title={image.orig_name}><small>{image.orig_name}</small></div>
									<small className="text-muted">{image.uploader_name || 'Unknown'}</small>
									<div className="mt-2">
										<a href="#" className="me-3" onClick={e=>{e.preventDefault(); copyMarkdown(image);}} title="Copy markdown"><FontAwesomeIcon icon={faCopy} /></a>
										<a href="#" style={{color:'red'}} onClick={e=>{e.preventDefault(); confirmAndDelete(image);}} title="Delete"><FontAwesomeIcon icon={faTrash} /></a>
									</div>
								</div>
							</div>
						</div>
					))}
				</div>
			)
		)}
	</AdminPage>);
}
