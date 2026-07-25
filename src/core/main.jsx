/**
 * main.jsx
 * The entry point for the app.
 */

import {StrictMode} from 'react';
import ReactDOM from 'react-dom/client';
import {BrowserRouter as Router, Route, Routes} from "react-router-dom";
import ReactGA from "react-ga4";

import {App} from '#App';
import config from '#config/server';
import app_config from '#config/app';
import {Home} from '#views/Home';
import {AllPosts} from '#views/AllPosts';
import {NewPost} from '#views/NewPost';
import {EditPost} from '#views/EditPost';
import {Search} from '#views/Search';
import {NotFound} from '#views/NotFound';
import {Post} from '#views/Post';
import {Settings} from '#views/Settings';
import {Users} from '#views/Users';
import {Media} from '#views/Media';
import {AnalyticsListener} from '#components/AnalyticsListener';

(async function main(){
	const rootDiv = document.getElementById('app_container');
	const reactRoot = ReactDOM.createRoot(rootDiv);

	if(app_config?.ga_tag){
		ReactGA.initialize(app_config?.ga_tag);
	}

	reactRoot.render(<StrictMode>
			<Router basename={config.base_url}>
				<AnalyticsListener />
				<Routes>
					<Route path="/" element={<App />}>
						<Route path="/" element={<Home />} />
						<Route path="/admin" element={<AllPosts />} />
						<Route path="/new_post" element={<NewPost />} />
						<Route path="/post/:slugOrId" element={<Post />} />
						<Route path="/edit_post/:slugOrId" element={<EditPost />} />
						<Route path="/search/:query" element={<Search />} />
						<Route path="/settings" element={<Settings />} />
						<Route path="/users" element={<Users />} />
						<Route path="/media" element={<Media />} />
						<Route path="*" element={<NotFound />}  />
					</Route>
				</Routes>
			</Router>
		</StrictMode>);
})();