import React from 'react'

export default class RepoList extends React.Component {
    constructor(props) {
        super(props);
        this.state = {
            list: props.list
        }
    }
    handleClick(item) {
        if (!this.handleClickFuncs) this.handleClickFuncs = {};
        if (!this.handleClickFuncs[item]) {
            this.handleClickFuncs[item] = ev => {ev.preventDefault(); this.props.go('repo', [item]) };
        }
        return this.handleClickFuncs[item];
    }
    renderRow = (item) => {
        return <tr>
            <td><span className="link" onClick={this.handleClick(item.name)} >{item.name}</span></td>
        </tr>;
    }
    render() {
        return <table>
            {this.state.list.map(this.renderRow)}
        </table>;
    }
}