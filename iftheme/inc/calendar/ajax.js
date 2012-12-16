// JavaScript Document

// Classe AJAX
// http://blog.neovov.com/index.php?2006/12/03/122-mr-propre#sommaire

Function.prototype.bind = function(object) {
	var __method = this;
	return function() {
		return __method.apply(object, arguments);
	}
}

function XHR() {
	this.conn = false;
	this.data = new Array();
	this.autoReset = true;
	this.autoSend = false;
	this.sendMethod = "POST";
	this.url = new String();

	if (window.XMLHttpRequest) {
		try { this.conn = new window.XMLHttpRequest(); }
		catch (e) { return false; }
	} else {
		var msXML = new Array(
			"Msxml2.XMLHTTP.5.0",
			"Msxml2.XMLHTTP.4.0",
			"Msxml2.XMLHTTP.3.0",
			"Msxml2.XMLHTTP",
			"Microsoft.XMLHTTP");
		var nbMsXML = msXML.length;
		for (var i = 0; i < nbMsXML; i++) {
			try {
				this.conn = new ActiveXObject(msXML[i]);
				break;
			} catch (e) { }
		}
		if (!this.conn) { return false; }
	}

	this.conn.onreadystatechange = (function () {
		switch (this.conn.readyState) {
			case 0 : this.uninitialized(this.conn);
				break;

			case 1 : this.loading(this.conn);
				break;

			case 2 : this.loaded(this.conn);
				break;

			case 3 : this.interactive(this.conn);
				break;

			case 4: this.complete(this.conn);
				break;
		}
	}).bind(this);
}

XHR.prototype.uninitialized	= new Function;
XHR.prototype.loading		= new Function;
XHR.prototype.loaded		= new Function;
XHR.prototype.interactive	= new Function;
XHR.prototype.complete		= new Function;

XHR.prototype.autoResetOff = function ()	{ this.autoReset = false; }
XHR.prototype.autoResetOn  = function ()	{ this.autoReset = true;  }
XHR.prototype.autoSendOff  = function ()	{ this.autoSend  = false; }
XHR.prototype.autoSendOn   = function ()	{ this.autoSend  = true;  }

XHR.prototype.setSendMethod = function (method) {
	method = method.toUpperCase();
	switch(method){
		case "GET": this.sendMethod = "GET";
		break;

		case "POST": this.sendMethod = "POST";
		break;

		default: this.sendMethod = "POST";
	}
}

XHR.prototype.switchSendMethod = function() {
	if (this.sendMethod == "POST") this.sendMethod = "GET";
	else this.sendMethod = "POST";
}

XHR.prototype.appendData = function (field, value) {
	for (var i = 0, j = this.data.length; i < j; i++) {
		if (this.data[i]["field"] == field) {
			this.data[i]["value"] = value;
			return (this.autoSend) ? this.send() : true;
		}
	}
	this.data.push(new Array());
	this.data[this.data.length - 1]["field"] = field;
	this.data[this.data.length - 1]["value"] = value;
	return (this.autoSend) ? this.send() : true;
}

XHR.prototype.resetData = function () {
	delete this.data;
	this.data = new Array();
	return true;
}

XHR.prototype.prepareData = function () {
	var prepared = new String();
	for (var i = 0, j = this.data.length; i < j; i++) {
		prepared += encodeURIComponent(this.data[i]["field"])
		if (this.data[i]["value"].length > 0) prepared += "=" + encodeURIComponent(this.data[i]["value"]);
		if (i < j - 1) prepared += "&";
	}
	return prepared;
}

XHR.prototype.setField = function (field, newField) {
	for (var i = 0, j = this.data.length; i < j; i++) {
		if (this.data[i]["field"] == field) {
			this.data[i]["field"] = newField;
			return (this.autoSend) ? this.send() : true;
		}
	}
	return false;
}

XHR.prototype.deleteField = function (field) {
	var newData = new Array();
	var newIndex = 0;
	var deleted = false;
	for (var i = 0, j = this.data.length; i < j; i++) {
		if (this.data[i]["field"] != field) {
			newData.push(new Array());
			newData[newIndex]["field"] = this.data[i]["field"];
			newData[newIndex]["value"] = this.data[i]["value"];
			newIndex ++;
		} else { deleted = true }
	}
	this.data = newData;
	return (deleted) ? ((this.autoSend) ? this.send() : true) : false;
}

XHR.prototype.setValue = function (field, value) {
	for (var i = 0, j = this.data.length; i < j; i++) {
		if (this.data[i]["field"] == field) {
			this.data[i]["value"] = value;
			return (this.autoSend) ? this.send() : true;
		}
	}
	return false;
}

XHR.prototype.deleteValue = function (field) {
	for (var i = 0, j = this.data.length; i < j; i++) {
		if (this.data[i]["field"] == field) {
			this.data[i]["value"] = new String;
			return (this.autoSend) ? this.send() : true;
		}
	}
	return false;
}

XHR.prototype.send = function (url) {
	if (!url && this.url.length == 0) return false;
	if (!url) url = this.url;
	else this.url = url;

	var preparedData = this.prepareData();

	switch(this.sendMethod){
		case "GET":
			try {
				if (preparedData.length > 0) url += url + "?" + data;
				this.conn.open("GET", url, true);
				this.conn.send(null);
			} catch (e) { return e; }
			break;

		case "POST":
			try {
				this.conn.open("POST", url, true);
				this.conn.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				this.conn.send(preparedData);
			} catch (e) { return e; }
			break;
	}

	if(this.autoReset) this.resetData();
	return true;
}