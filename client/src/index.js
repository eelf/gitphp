import React, {Component} from 'react';
import ReactDOM from 'react-dom';
import FastRoute from 'fast-route';

import Events from './misc/EventEmitter';

import RepoList from './pages/RepoList';
import Repo from './pages/Repo';
import Commit from './pages/Commit';

class App extends Component {
    constructor() {
        super();
        this.state = {
            page: 'init',
            page_data: null,
        };
        this.route = new FastRoute;
        this.route.addRoute('GET', '/', 'repo_list');
        this.route.addRoute('GET', '/repo/{repo:string}', 'repo');
        this.route.addRoute('GET', '/repo/{repo:string}/commit/{commit:string}', 'commit');

        window.addEventListener('popstate', this.popstate, false);

        call_method('init', {}, this.handleInit, this.handleError);
    }

    pageToUrl(page, args) {
        if (page == 'repo_list') return '/';
        else if (page == 'repo') return '/repo/' + args[0];
        else if (page == 'commit') return '/repo/' + args[0] + '/commit/' + args[1];
        return '/';
    }
    urlToPage(url) {
        let result = this.route.dispatch('GET', url);
        console.log('urlToPage', url, result);
        if (result.status == 200) {
            return {page: result.handler, args: Object.values(result.params || {})};
        }
        return {page: 'repo_list', args: []};
    }

    pushState = (data, title, url) => {
        console.log('pushState', data, title, url);
        history.pushState(data, title, url);
    }
    popstate = popsev => {
        let page = popsev.state && popsev.state.page || 'repo_list';
        let args = popsev.state && popsev.state.args || [];
        console.log('popstate', popsev, page, args);
        if (page == 'repo_list') call_method('list_repositories', {}, this.handleListRepositories, this.handleError);
        else if (page == 'repo') call_method('show_repo', {name: args[0]}, this.handleShowRepo, this.handleError);
        else if (page == 'commit') call_method('show_commit', {repo: args[0], hash: args[1]}, this.handleShowCommit, this.handleError);
    }
    go = (page, args) => {
        console.log('go', page, args);
        this.pushState({page: page, args: args}, 'gitphp: ' + page, this.pageToUrl(page, args));
        this.popstate({state: {page: page, args: args}});
    }

    handleError = (text, ...args) => {
        console.log('App.handleError', text, args);
    }
    handleInit = (r) => {
        // switch auth
        // err: go auth
        // have location: go location.search
        // default: go repos
        console.log('handleInit', r);
        let res = this.urlToPage(location.pathname);
        console.log('handleInit urlToPage', res);
        this.popstate({state: res});
    }
    handleListRepositories = (r) => {
        console.log('handleListRepositories', r);
        if (r.error_code) {
            history.back();
            return;
        }
        let page = 'repo_list';
        this.setState({page: page, page_data: r});
    }
    handleShowRepo = (r) => {
        console.log('handleShowRepo', r);
        if (r.error_code) {
            history.back();
            return;
        }
        let page = 'repo';
        this.setState({page: page, page_data: r});
    }
    handleShowCommit = (r) => {
        console.log('handleShowCommit', r);
        let page = 'commit';
        this.setState({page: page, page_data: r});

        if (Object.keys(r.diff).length <= 10) {
            call_method('get_diff', {repo: r.repo, hash: r.hash, parent: r.parents, names: Object.keys(r.diff)}, this.handleGetDiff, this.handleError);
        } else {
            call_method('get_diff', {repo: r.repo, hash: r.hash, parent: r.parents, names: Object.keys(r.diff).slice(0, 10)}, this.handleGetDiff, this.handleError);
        }
    }
    handleGetDiff = (r) => {
        let pd = this.state.page_data;
        console.log('handleGetDiff', r, pd);
        Object.keys(r.diffs).forEach(name => pd.diff[name].diffs = r.diffs[name]);
        console.log('handleGetDiff after', pd);
        this.setState({page_data: pd});
        // Events.emit('diff_update', r);
    }

    handleClickLogo = () => {
        this.go('repo_list');
    }

    render() {
        let page;
        if (this.state.page == 'init') page = <div>keep calm and om nom nom</div>;
        else if (this.state.page == 'repo_list') page = <RepoList list={this.state.page_data.list} go={this.go} />;
        else if (this.state.page == 'repo') page = <Repo name={this.state.page_data.repo} log={this.state.page_data.log}
                                                         heads={this.state.page_data.heads}
                                                         go={this.go} />;
        else if (this.state.page == 'commit') page = <Commit name={this.state.page_data.repo} d={this.state.page_data}
                                                         go={this.go} />;

        return <div><div className="logo" onClick={this.handleClickLogo}>code_is_ok</div>
            {page}
        </div>;
    }
}

function call_method(method, data, cb, errCb) {
    ajaj('POST', '/api.php', Object.assign({method: method}, data), cb, errCb);
}

function ajaj(method, url, data, cb, errCb, ctx)  {
    var x = new XMLHttpRequest();

    x.open(method, url);
    x.ontimeout = (...a) => errCb.call(ctx, 'timeout', a);
    x.onerror = (...a) => errCb.call(ctx, 'ajaj error', a);
    x.onload = function () {
        try {
            x.responseJson = JSON.parse(x.response);
        } catch (e) {
            errCb.call(ctx, 'json.parse failed', e, x.response);
            return;
        }
        cb.call(ctx, x.responseJson);
    };
    x.withCredentials = true;
    if (data !== null) {
        x.setRequestHeader('Content-Type', 'application/json');
        x.send(JSON.stringify(data));
    } else {
        x.send();
    }
}

ReactDOM.render(<App />, document.getElementById('react_root'));
