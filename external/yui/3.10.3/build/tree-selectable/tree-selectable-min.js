/*
YUI 3.10.3 (build 2fb5187)
Copyright 2013 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/

YUI.add("tree-selectable",function(e,t){function s(){}function o(){}var n=e.Do,r="select",i="unselect";s.prototype={initializer:function(){this.nodeExtensions=this.nodeExtensions.concat(e.Tree.Node.Selectable),this._selectedMap={},n.after(this._selectableAfterDefAddFn,this,"_defAddFn"),n.after(this._selectableAfterDefClearFn,this,"_defClearFn"),n.after(this._selectableAfterDefRemoveFn,this,"_defRemoveFn"),this._selectableEvents=[this.after("multiSelectChange",this._afterMultiSelectChange)]},destructor:function(){(new e.EventHandle(this._selectableEvents)).detach(),this._selectableEvents=null,this._selectedMap=null},getSelectedNodes:function(){return e.Object.values(this._selectedMap)},selectNode:function(e,t){return this._selectedMap[e.id]||this._fireTreeEvent(r,{node:e,src:t&&t.src},{defaultFn:this._defSelectFn,silent:t&&t.silent}),this},unselect:function(e){for(var t in this._selectedMap)this._selectedMap.hasOwnProperty(t)&&this.unselectNode(this._selectedMap[t],e);return this},unselectNode:function(e,t){return(e.isSelected()||this._selectedMap[e.id])&&this._fireTreeEvent(i,{node:e,src:t&&t.src},{defaultFn:this._defUnselectFn,silent:t&&t.silent}),this},_selectableAfterDefAddFn:function(e){e.node.isSelected()&&this.selectNode(e.node)},_selectableAfterDefClearFn:function(){this._selectedMap={}},_selectableAfterDefRemoveFn:function(e){delete e.node.state.selected,delete this._selectedMap[e.node.id]},_afterMultiSelectChange:function(){this.unselect()},_defSelectFn:function(e){this.get("multiSelect")||this.unselect(),e.node.state.selected=!0,this._selectedMap[e.node.id]=e.node},_defUnselectFn:function(e){delete e.node.state.selected,delete this._selectedMap[e.node.id]}},s.ATTRS={multiSelect:{value:!1}},e.Tree.Selectable=s,o.prototype={isSelected:function(){return!!this.state.selected},select:function(e){return this.tree.selectNode(this,e),this},unselect:function(e){return this.tree.unselectNode(this,e),this}},e.Tree.Node.Selectable=o},"true",{requires:["tree"]});