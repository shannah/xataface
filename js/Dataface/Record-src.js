function Dataface_Record(vals){
	this.vals = vals;
}
new Dataface_Record({});
Dataface_Record.prototype.getURL = function(arg){
	return this.vals['__url__']+'&'+arg;
}

Dataface_Record.prototype.getTable = function(){
	return this.vals['__id__'].substring(0, this.vals['__id__'].indexOf('?'));
}
