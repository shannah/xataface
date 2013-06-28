if ( typeof Dataface == 'undefined' ) Dataface = {};
Dataface.Calendar = function(id, date){
	if ( !id ) id = 'df-calendar-'+Math.random();
	if ( !date ) date = new Date();
	if ( typeof date != 'Date' ){
		var d = new Date();
		d.setTime(date);
		date = d;
	}
	this.selectedDate = date;
	this.id = id;
	Dataface.Calendar.addInstance(this);
	
};

Dataface.Calendar.instances = {};
Dataface.Calendar.addInstance = function(calendar){
	this.instances[calendar.id] = calendar;
};
Dataface.Calendar.getInstance = function(id){
	return this.instances[id];
};

Dataface.Calendar.prototype = {
	'selectedEvent': null,
	'selectedDate': new Date(),
	'defaultStartTime': 8,
	'defaultEndTime': 22,
	'monthPanel':null,
	'weekPanel':null,
	'dayPanel':null,
	'detailsPanel':null,

	
	'handleSelectDay': function(date){
		if ( typeof date != 'Date' ){
			date = new Date(date);
		}
		this.selectedDate = date;
		if ( this.dayPanel ){
			document.getElementById(this.dayPanel).innerHTML = this.drawDay(9,21,0.25);
		}
		
	},
	
	'handleSelectEvent': function(event){
		if ( this.selectedEvent ){
			var eventDiv = document.getElementById('event-preview-'+this.selectedEvent.id);
			
			var cls = eventDiv.className; //eventDiv.getAttribute('class');
			if ( !cls ) cls = '';
			eventDiv.className =  cls.replace(/Dataface-Calendar-selected-event/, ''); //eventDiv.setAttribute('class', cls.replace(/Dataface-Calendar-selected-event/, ''));
		
		}
		this.selectedEvent = Dataface.Calendar.Event.getInstance(event);
		if ( this.detailsPanel ){
			document.getElementById(this.detailsPanel).innerHTML = this.selectedEvent.showDetails();
			
			
		}
		var div = document.getElementById('event-preview-'+this.selectedEvent.id);
		var cls2 = div.className; //div.getAttribute('class');
		if ( !cls2 ) cls2 = '';
		if ( div ) div.className =  cls2+' Dataface-Calendar-selected-event'; //div.setAttribute('class', cls2+' Dataface-Calendar-selected-event');

	}
	
};

Dataface.Calendar.Event = function(id, data){
	this.id = 'event-'+Math.random();
	for ( var key in data){
		if ( (key == 'date' || key == 'endTime') && typeof data[key] != 'Date' ){
			var d = new Date();
			d.setTime(data[key]+d.getTimezoneOffset()*60*1000);
			data[key] = d;
			
		}
		this[key] = data[key];
	}
	Dataface.Calendar.Event.addInstance(this);
};

Dataface.Calendar.Event.instances = {};
Dataface.Calendar.Event.addInstance = function(event){
	this.instances[event.id] = event;
};
Dataface.Calendar.Event.getInstance = function(id){
	return this.instances[id];
};



Dataface.Calendar.Event.prototype = {
	'title': null,
	'date': null,
	'endTime': null,
	'description': null,
	'url': null,
	'showDetails': function(){
		var out = '<div "Dataface-Calendar-details">';
		out += '<h3 class="Dataface-Calendar-details-title">'+this.title+'</h3>';
		out += '<table class="Dataface-Calendar-details-data"><tbody>';
		out += '<tr><th>from</th><td>'+this.date.asString('%Y-%m-%d')+' at '+this.date.asString('%H:%i')+'</td></tr>';
		if ( this.endTime ) {
			out += '<tr><th>to</th><td>'+this.endTime.asString('%Y-%m-%d')+' at '+this.endTime.asString('%H:%i')+'</td></tr>';
		}
		out += '</table>';
		out += '<div class="Dataface-Calendar-details-description">'+this.getDescription()+'</div>';
	
		out += '	</div>';
		return out;
	},
	'getDescription': function(){
		return this.description;
	}
};




Dataface.Calendar.prototype.events = {
	'list': [],
	'sorted': false,
	
	'add': function(e){
		this.list[this.list.length] = e;
		this.sorted = false;
	},
	
	'compare': function(a,b){
		return (a.date.getTime() < a.date.getTime());
	},
	
	'sort': function(){
		if ( !this.sorted ){
			this.list.sort(this.compare);
		}
	},
	
	'month': function(date){
		this.sort();
		var out = [];
		for ( var i=0; i<this.list.length; i++){
			var e = this.list[i];
			if ( (e.date.getFullYear() == date.getFullYear()) && (e.date.getMonth() == date.getMonth()) ){
				out[out.length] = this.list[i];
			}
		}
		return out;
	},
	
	'day': function(date){
		this.sort();
		var out = [];
		for ( var i=0; i<this.list.length; i++){
			var e = this.list[i];
			if ( (e.date.getFullYear() == date.getFullYear()) && 
				 ( e.date.getMonth() == date.getMonth() ) && 
				 ( e.date.getDate() == date.getDate() ) ){
				out[out.length] = this.list[i];
			}
		}
		return out;
	},
	
	'week': function(date){
		this.sort();
		var firstDay = date.getDate() - date.getDay();
		var lastDay = date.getDate() + ( 6 - date.getDay() );
		
		var out = [];
		for ( var i=0; i<this.list.length; i++){
			var e = this.list[i];
			if ( (e.date.getFullYear() == date.getFullYear()) && 
				 ( e.date.getMonth() == date.getMonth() ) && 
				 ( e.date.getDate() >= firstDay ) &&
				 ( e.date.getDate() <= lastDay ) ){
				out[out.length] = this.list[i];
			}
		}
		return out;
		
	},
	
	'range': function(start,end){
		this.sort();
		var out = [];
		for ( var i =0; i<this.list.length; i++ ){
			var e = this.list[i];
			
			if ( (start.getTime() <= e.date.getTime()) && (end.getTime() > e.date.getTime()) ){
				out[ out.length ] = e;
			}
		}
		return out;
	}
	

};


Date.daysOfWeek = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
Date.monthsOfYear = ['January','February','March','April','May','June','July','August','September','October','November','December'];


Date.prototype.daysInMonth = function(/*iMonth, iYear*/){
	return 32 - new Date(this.getFullYear(), this.getMonth(), 32).getDate();
}

Dataface.Calendar.prototype.drawMonth = function(){
	var firstDay = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), 1);
	
	var out = '<table class="Dataface-Calendar-month" cellspacing="0"><thead><tr><th>'+Date.daysOfWeek.join('</th><th>')+'</th></tr></thead>';
	out += '<tbody>';
	
	var day = -1;
	for ( var i=0; i<5; i++ ){
		out += '<tr>';

		for ( var j=0; j<7; j++ ){
			
			
			if ( (day == -1) && ( firstDay.getDay() ==  j ) ){
				// We have arrived at the first day of the month so we can start the day counter
				day = 0;
			}
			
			if ( day == this.selectedDate.daysInMonth() ){
				day = -1;
			}
			var cls = '';
			if ( day >= 0 ) cls = 'Dataface-Calendar-day';
			else cls = 'Dataface-Calendar-empty-day';
			
			out += '<td class="'+cls+'"><div class="day-wrapper"';
			
			if ( day >= 0 ){
				var currDay = this.selectedDate.clone();
				currDay.setDate(day+1);
				out += '<div class="day-number"><a href="javascript:Dataface.Calendar.getInstance(\''+this.id+'\').handleSelectDay(\''+currDay.toString()+'\')">'+(day+1)+'</a></div>';
				
				
				//var events = Date.events.range(startTime, endTime);
				var events = this.events.day(currDay);
				
				for ( var k=0; k<events.length; k++){
					out += '<div class="Dataface-Calendar-event" id="event-preview-'+events[k].id+'"><a href="javascript:Dataface.Calendar.getInstance(\''+this.id+'\').handleSelectEvent(\''+events[k].id+'\');">'+events[k].title+'</a></div>';
				}
				
				day++;
			}
			
			out += '</div></td>';
			
		}
		out += '</tr>';
		
	}
	out += '</tbody></table>';
	return out;
};

Dataface.Calendar.prototype.drawWeek = function(startHour, endHour){

	if ( !startHour ) startHour = 8;
	if ( !endHour ) endHour = 20;

	var thisDayOfWeek = this.selectedDate.getDay();
	var thisWeeksFirstDay = this.selectedDate.getDate()-this.selectedDate.getDay();
	
	var headings = [];
	
	for ( var i=0; i<Date.daysOfWeek.length; i++){
		var date = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), thisWeeksFirstDay+i);
		headings[headings.length] = Date.daysOfWeek[i]+' '+Date.monthsOfYear[ date.getMonth() ]+' '+date.getDate()+', '+date.getFullYear();
	}
	var out = '<table class="jsCalendar-week"><thead><tr><th></th><th>'+headings.join('</th><th>')+'</th></tr></thead>';
	out += '<tbody>';
	for ( var hour=startHour; hour<=endHour; hour++ ){
		out += '<tr><th>'+(hour+1)+':00</th>';
		for ( var j=0; j<Date.daysOfWeek.length; j++){
			out += '<td></td>';
		}
		out += '</tr>';
	}
	out += '</tbody></table>';
	
	return out;
	
	
};

Dataface.Calendar.prototype.drawDay = function(startHour, endHour, precision){
	if ( !startHour ) startHour = 8;
	if ( !endHour ) endHour = 20;
	if ( !precision ) precision = 1.0;

	var thisDayOfWeek = this.selectedDate.getDay();
	var thisWeeksFirstDay = this.selectedDate.getDate()-this.selectedDate.getDay();
	
	var headings = [];
	
	for ( var i=0; i<Date.daysOfWeek.length; i++){
		var date = new Date(this.selectedDate.getFullYear(), this.selectedDate.getMonth(), thisWeeksFirstDay+i);
		headings[headings.length] = Date.daysOfWeek[i]+' '+Date.monthsOfYear[ date.getMonth() ]+' '+date.getDate()+', '+date.getFullYear();
	}
	var out = '<table class="jsCalendar-week"><thead><tr><th></th><th>'+headings[this.selectedDate.getDay()]+'</th></thead>';
	out += '<tbody>';
	var startTimems = this.selectedDate.clone();
	startTimems.setHours(startHour);
	startTimems.setMinutes(0);
	startTimems.setSeconds(0);
	
	var endTimems = this.selectedDate.clone();
	endTimems.setHours(endHour);
	endTimems.setMinutes(0);
	endTimems.setSeconds(0);
	
	//for ( var hour=startHour; hour<=endHour; hour++ ){
	for ( var time=startTimems.getTime(); time <= endTimems; time += (precision*60*60*1000) ){
		
		var startTime = this.selectedDate.clone();
		startTime.setTime(time);
		var minutes = startTime.getMinutes() + "";
		if ( minutes.length == 1 ) minutes = '0'+minutes;
		out += '<tr><th>'+(startTime.getHours())+':'+minutes+'</th>';
		//startTime.setHours(hour);
		//startTime.setMinutes(0);
		//startTime.setSeconds(0);
		
		var endTime = this.selectedDate.clone();
		endTime.setTime(time + (precision*60*60*1000) );
		//endTime.setHours(hour+1);
		//endTime.setMinutes(0);
		//endTime.setSeconds(0);
		
		//var events = Date.events.range(startTime, endTime);
		var events = this.events.range(startTime, endTime);
		out += '<td>';
		for ( var j=0; j<events.length; j++){
			out += '<div class="jsCalendar-event"><a href="javascript:Dataface.Calendar.getInstance(\''+this.id+'\').handleSelectEvent(\''+events[j].id+'\');">'+events[j].title+'</a></div>';
		}
		out += '</td></tr>';
	}
	out += '</tbody></table>';
	
	return out;
};

Date.prototype.clone = function(){
	return new Date(this.getFullYear(), this.getMonth(), this.getDate(), this.getHours(), this.getMinutes(), this.getSeconds());
};

Date.prototype.asString = function (format){
	var out = format.replace(/%Y/, this.getFullYear());
	out = out.replace(/%m/, pad(this.getMonth()+1,2));
	out = out.replace(/%d/, pad(this.getDate(),2));
	out = out.replace(/%H/, pad(this.getHours(),2));
	out = out.replace(/%i/, pad(this.getMinutes(),2));
	return out;
};

function pad(number, length) {
   
    var str = '' + number;
    while (str.length < length) {
        str = '0' + str;
    }

    return str;

}



