import React from 'react'
import Events from './../misc/EventEmitter';

export default class Commit extends React.Component {
    constructor(props) {
        super(props);
    }

    handleClickCommit = hash => {
        if (!this.handleClickCommitFuncs) this.handleClickCommitFuncs = {};
        if (!this.handleClickCommitFuncs[hash]) {
            this.handleClickCommitFuncs[hash] = ev => {
                console.log('clicked on hash', hash);
                this.props.go('show_commit', [this.props.name, hash]);
            }
        }
        return this.handleClickCommitFuncs[hash];
    }

    renderDiffRow = (diff_name) => {
        let diff = this.props.d.diff[diff_name];
        return <li key={diff.name}>
            {diff.change_type} {diff.name}
        </li>;
    }
    renderDiff = (diff_name) => {
        let diff = this.props.d.diff[diff_name];
        let lines = diff.diffs || [];
        console.log('renderDiff', diff_name, diff, Object.assign({}, lines));
        return <table key={diff_name} className="diff">
            <tbody>
            {lines.map(l => <tr key={diff_name+"-"+l.before+"-"+l.after+"-"+l.collapsed} className={!l.before ? 'green' : !l.after ? 'red' : 'gray'}>
                <td>{l.before}</td><td>{l.after}</td><td>{l.line}</td>
            </tr>)}
            </tbody>
            </table>;
    }
    render() {
        return <div>Commit
            <div>{this.props.d.subject}</div>
            <div>{this.props.d.author_name} {this.props.d.author_ts}</div>
            <ul>
                {Object.keys(this.props.d.diff).map(this.renderDiffRow)}
            </ul>
            {Object.keys(this.props.d.diff).map(this.renderDiff)}
        </div>;
    }
}