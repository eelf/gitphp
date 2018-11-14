import React from 'react'

export default class Repo extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            name: this.props.name,
            log: this.props.log,
            heads: this.props.heads
        };
    }

    handleClickCommit = hash => {
        if (!this.handleClickCommitFuncs) this.handleClickCommitFuncs = {};
        if (!this.handleClickCommitFuncs[hash]) {
            this.handleClickCommitFuncs[hash] = ev => {
                console.log('clicked on hash', hash);
                this.props.go('commit', [this.props.name, hash]);
            }
        }
        return this.handleClickCommitFuncs[hash];
    }

    handleClickLogo = () => {
        this.props.go('repo_list');
    }

    renderLogRow = item => {
        return <tr key={item.hash} onClick={this.handleClickCommit(item.hash)}>
            <td>{(new Date(item.time * 1000)).toString()}</td>
            <td>{item.author}</td>
            <td>{item.subject}</td>
        </tr>;
    }
    render() {
        return <div>repo {this.state.name}
            <h4>Log</h4>
            <table><tbody>{this.state.log.map(this.renderLogRow)}</tbody></table>
        </div>;
    }
}