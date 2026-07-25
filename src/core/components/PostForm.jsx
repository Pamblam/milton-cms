/**
 * PostForm.jsx
 * The form that is used to edit or create a new post.
 */

import {useContext, useState, useRef, useEffect, useCallback} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faImage, faCircleCheck } from '@fortawesome/free-solid-svg-icons';

import {useNavHelper} from '#hooks/useNavHelper';
import { APIRequest } from '#modules/APIRequest';
import { FI } from '#modules/FI';
import { AppStateContext } from '#App';

export function PostForm({slugOrId}){
	const {userSession} = useContext(AppStateContext);
	const navigate = useNavHelper();
	const [errorMessage, setErrorMessage] = useState();
	const [successMessage, setSuccessMessage] = useState();
	const [postData, setPostData] = useState({});

	const new_post_title_ref = useRef();
	const new_post_summary_ref = useRef();
	const new_post_tags_ref = useRef();
	const textarea_ref = useRef();
	const preview_tab_ref = useRef();
	const new_post_preview_ref = useRef();
	const img_btn_ref = useRef();
	const fi_instance_ref = useRef();
	const graph_img_ref = useRef();
	const new_post_publish_ref = useRef();
	const post_id_ref = useRef();
	const submitting_ref = useRef(false);

	// Track the textarea's caret / selection so an inserted image lands where
	// the user last had their cursor — even after focus moves to the Insert
	// Image button (a textarea keeps its selection after blur). Updated on
	// every interaction that can move the caret: clicks, key-ups and selects.
	const selection = useRef({start: null, end: null});
	const updateSelection = () => {
		const ta = textarea_ref.current;
		if(!ta) return;
		selection.current = {start: ta.selectionStart, end: ta.selectionEnd};
	};

	useEffect(()=>{
		if(Object.keys(postData).length){
			post_id_ref.current = postData.post.id;
			graph_img_ref.current = postData.post.graph_img;
			if(new_post_title_ref.current) new_post_title_ref.current.value = postData.post.title;
			if(new_post_summary_ref.current) new_post_summary_ref.current.value = postData.post.summary;
			if(textarea_ref.current) textarea_ref.current.value = postData.post.body;
			if(new_post_publish_ref.current) new_post_publish_ref.current.checked = postData.post.published == 1;
			if(new_post_tags_ref.current) new_post_tags_ref.current.value = postData.tags.map(tag=>tag.tag).join(', ');
		}
	}, [postData]);

	useEffect(()=>{
        if(slugOrId) (async ()=>{
            let res;
            if(/^\d+$/.test(slugOrId)){
                res = await new APIRequest(`Post/${slugOrId}`).get({raw:1});
            }else{
                res = await new APIRequest(`Post`).get({slug:slugOrId, raw:1});
            }
            if(res.has_error){
                navigate('/404')
            }
			setPostData(res.data);
        })();
    }, []);

	const autoExpandTextarea = function(e){
		if(e) e.stopPropagation();
		let sy = scrollY;
		this.setAttribute('rows', 1);
		var cs = getComputedStyle(this);
		var paddingTop = +cs.paddingTop.substr(0, cs.paddingTop.length-2);
		var paddingBottom = +cs.paddingBottom.substr(0, cs.paddingBottom.length-2);
		var lineHeight = +cs.lineHeight.substr(0, cs.lineHeight.length-2);
		var rows = (this.scrollHeight - (paddingTop + paddingBottom)) / lineHeight;
		this.setAttribute('rows', rows);
		scrollTo({top: sy, behavior:'instant'});
	};

	const onSumbit = async e=>{
		e.preventDefault();
		if(submitting_ref.current) return;
		submitting_ref.current = true;
		const title = new_post_title_ref.current.value.trim();
		const summary = new_post_summary_ref.current.value.trim();
		const body = textarea_ref.current.value.trim();
		const publish = new_post_publish_ref.current.checked ? 1 : 0;
		const graph_img = graph_img_ref.current;
		const post_id = post_id_ref.current;
		const tags = new_post_tags_ref.current.value.trim();

		let props = {
			title,
			summary,
			body,
			publish,
			tags
		};

		const graph_img_match = body.match(/!\[[^\]]*\]\((assets\/[^\)]+)\)/);
		if(graph_img_match) props.graph_img = graph_img_match[1];
		else if(graph_img) props.graph_img = graph_img;

		let verbiage1 = publish == 1 ? 'Post' : 'Draft'
		let verbiage2 = post_id ? 'updated' : 'saved'
		let verbiage3 = publish == 1 ? 'published' : 'preview'
		let time = new Date().toLocaleTimeString();

		let res = post_id ?
			await new APIRequest(`Post/${post_id}`, userSession).patch(props):
			await new APIRequest('Post', userSession).post(props);

		if(res.has_error){
			setSuccessMessage(undefined);
			setErrorMessage(res.message);
		}else{
			if(res?.data?.Post?.id) post_id_ref.current = res.data.Post.id;
			let link = location.href.replace(/(\/new_post|\/edit_post\/[^\/]*)\/?/, `/post/${res.data.Post.slug}`);
			let path = location.href.split('#').shift().split('?').shift();
			if(/new_post\/?$/.test(path)){
				path = path.replace(/new_post\/?$/, `edit_post/${res.data.Post.slug}`)
				window.history.replaceState(null, "", path)
			}
			let message = `${verbiage1} ${verbiage2} at ${time}<br><small>${verbiage1} ${verbiage3}: <a href='${link}' target=_blank>${link}</a></small>`;
			let pd = Object.assign({}, postData);
			pd.post = res.data.Post;
			pd.tags = res.data.Tags;
			setPostData(pd);
			setErrorMessage(undefined);
			setSuccessMessage(message);
		}

		submitting_ref.current = false;
	};

	const set_new_post_title_ref = useCallback(node=>{
		if (node){
			new_post_title_ref.current = node;
			if(postData.post) new_post_title_ref.current.value = postData.post.title;
		} 
	});

	const set_new_post_summary_ref = useCallback(node=>{
		if (node){
			new_post_summary_ref.current = node; 
			if(postData.post) new_post_summary_ref.current.value = postData.post.summary;
		} 
	});

	const set_new_post_tags_ref = useCallback(node=>{
		if (node){
			new_post_tags_ref.current = node; 
			if(postData.post) new_post_tags_ref.current.value = postData.post.tags;
		} 
	});

	const set_new_post_publish_ref = useCallback(node=>{
		if (node){
			new_post_publish_ref.current = node; 
			if(postData.post) new_post_publish_ref.current.checked = postData.post.published === 1;
		}
	});

	const set_img_btn_ref = useCallback(node=>{
		if (!node) return;

		if (img_btn_ref.current) {
			fi_instance_ref.current.destroy();
		}
		img_btn_ref.current = node;
		
		fi_instance_ref.current = new FI({
			button: node,
			accept: ["png", "jpg", "jpeg", "gif"],
			multi: false
		});
		
		fi_instance_ref.current.register_callback(async function(){

			let files = fi_instance_ref.current.get_files();
			if(!files || !files.length) return;
			let file = files[0];
			fi_instance_ref.current.clear_files();
			
			let res = {};
			try{
				res = await new APIRequest('Image', userSession).post({img: file});
			}catch(e){
				res = {
					has_error: true,
					message: "Unable to upload file. Please check that it does not exceed the size restrictions."
				};
			}

			if(res.has_error){
				setErrorMessage(res.message);
				return;
			}else{
				setErrorMessage(undefined);
			}

			if(!graph_img_ref.current){
				graph_img_ref.current = res.data.path;
			}

			let img_md = `![](${encodeURI(res.data.path)})`;

			const ta = textarea_ref.current;
			let md_text = ta.value;
			const sel = selection.current;
			let caret;

			// If the user has placed their cursor in the textarea, insert there
			// (replacing any selected text); otherwise append the image on its
			// own line at the end.
			if(sel.start !== null){
				const start = Math.min(sel.start, md_text.length);
				const end = Math.min(sel.end, md_text.length);
				md_text = md_text.slice(0, start) + img_md + md_text.slice(end);
				caret = start + img_md.length;
			}else{
				let lines = md_text.split("\n");
				if(!lines.at(-1).trim()) lines[lines.length-1] = img_md;
				else lines.push(img_md);
				md_text = lines.join("\n");
				caret = md_text.length;
			}

			ta.value = md_text;
			autoExpandTextarea.call(ta);

			// Restore focus and drop the caret right after the inserted markdown
			// so the tracked selection stays valid for a subsequent insert.
			ta.focus();
			ta.setSelectionRange(caret, caret);
			selection.current = {start: caret, end: caret};

			new_post_preview_ref.current.innerHTML = '<p>Loading...</p>';
			res = await new APIRequest('ParseMD').get({md: md_text});
			if(!res.has_error) new_post_preview_ref.current.innerHTML = res.data.html;
			else setErrorMessage('Unable to process markdown');
		});
	});

	const set_textarea_ref = useCallback(node=>{
		if (textarea_ref.current) {
			textarea_ref.current.removeEventListener('input', autoExpandTextarea);
		}
		if (node) {
			textarea_ref.current = node;
			textarea_ref.current.addEventListener('input', autoExpandTextarea);
			if(postData.post){
				textarea_ref.current.value = postData.post.body;
				autoExpandTextarea.call(textarea_ref.current);
			}
		}
	});

	const getMDPreview = async function(e){
		e.stopPropagation();
		let text = textarea_ref.current.value;
		new_post_preview_ref.current.innerHTML = '<p>Loading...</p>';
		let res = await new APIRequest('ParseMD').get({md: text});
		if(!res.has_error) new_post_preview_ref.current.innerHTML = res.data.html;
		else setErrorMessage('Unable to process markdown');
	};

	const set_preview_tab_ref = useCallback(node=>{
		if (set_preview_tab_ref.current) {
			preview_tab_ref.current.removeEventListener('show.bs.tab', getMDPreview);
		}
		if(node) {
			preview_tab_ref.current = node;
			preview_tab_ref.current.addEventListener('show.bs.tab', getMDPreview);
		}
	});

	const set_new_post_preview_ref = useCallback(node=>{
		if (node) new_post_preview_ref.current = node;
	});

	return (<form onSubmit={onSumbit}>

			{successMessage && (<div className='alert alert-success alert-dismissible'><span dangerouslySetInnerHTML={{__html: successMessage}}></span><button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setSuccessMessage(undefined);}}></button></div>)}
			{errorMessage && (<div className='alert alert-danger alert-dismissible'>{errorMessage}<button type="button" className="btn-close" onClick={e=>{e.preventDefault(); setErrorMessage(undefined);}}></button></div>)}

			<div className="mb-3">
				<label className="form-label">Post Title</label>
				<input data-lpignore="true" type="text" className="form-control" ref={set_new_post_title_ref} />
				<div className="form-text">Give the post a title.</div>
			</div>

			<div className="mb-3">
				<label className="form-label">Post Summary</label>
				<input data-lpignore="true" type="text" className="form-control" ref={set_new_post_summary_ref} />
				<div className="form-text">Summary should be 2-4 sentences.</div>
			</div>

			<div className="clearfix">
				<button type="button" className="btn btn-primary float-end" ref={set_img_btn_ref}><FontAwesomeIcon icon={faImage} /> Insert Image</button>
				<ul className="nav nav-tabs" style={{borderBottom: 'none'}}>
					<li className="nav-item">
						<a className="nav-link active" data-bs-toggle="tab" href="#new_post_compose">Compose</a>
					</li>
					<li className="nav-item">
						<a ref={set_preview_tab_ref} className="nav-link" data-bs-toggle="tab" href="#new_post_preview">Preview</a>
					</li>
				</ul>
			</div>
			<div className="tab-content mb-3">
				<div className="tab-pane container active px-0 pt-3" id="new_post_compose">
					<textarea data-lpignore="true" ref={set_textarea_ref} className="form-control" onKeyUp={updateSelection} onClick={updateSelection} onSelect={updateSelection}></textarea>
					<div className="form-text">Compose your post using Markdown.</div>
				</div>
				<div className="tab-pane container fade px-0 pt-3" id="new_post_preview" ref={set_new_post_preview_ref}>
					<p>preview</p>
				</div>
			</div>

			<div className="mb-3">
				<label className="form-label">Tags</label>
				<input data-lpignore="true" type="text" className="form-control" ref={set_new_post_tags_ref} />
				<div className="form-text">A comma-separated list of tags.</div>
			</div>

			<div className="mb-3">
				<div className="form-check">
					<input className="form-check-input" type="checkbox" id="new_post_publish" defaultChecked={true} ref={set_new_post_publish_ref} />
					<label className="form-check-label" htmlFor="new_post_publish">
						Publish this Post?
					</label>
				</div>
			</div>

			<button className="mb-3 btn btn-primary" type='submit'><FontAwesomeIcon icon={faCircleCheck} /> Save</button>
		</form>);
} 