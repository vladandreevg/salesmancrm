(function() {

	window.hyperscript = function () {

		var me = this;

		this.charsList = '0123456789abcdefghijklmnopqrstuvwxyz';
		this.callbacks = [];
		this.iframe = window.frames.hsiframe;

		window.addEventListener("message", function(event){

			var cmd = event.data && typeof event.data.split === 'function' ? event.data.split(/:(.+)?/) : [''],
			args = [];

			//console.log(event.data);

			var name = cmd[0];

			if (cmd[1]) args = JSON.parse(cmd[1]);

			if (name == 'runCallback' && me.callbacks[args.callback]) {
				me.callbacks[args.callback](args.data);
			}

			if (name == 'ready') {
				$(document).trigger('hsready');
			}

			if (name == 'scriptClose') {
				$(document).trigger('scriptClose');
			}

			if (name == 'scriptDone') {
				$(document).trigger('scriptDone', args);
			}

		});

	};

	window.hyperscript.prototype.init = function () {};

	window.hyperscript.prototype.sendMessage = function (cmd, params, cb) {

		cmd += ':' + (!!params ? JSON.stringify(params) : '') + ':' + this.setCallback(cb);
		// console.log(cmd);
		this.iframe.postMessage(cmd, '*');

	};

	window.hyperscript.prototype.uniqid = function () {
		var s = '';
		for (var i = 0; i < 32; i++)
			s += this.charsList[Math.round(Math.random() * (this.charsList.length - 1))];
		return s;
	};

	window.hyperscript.prototype.setCallback = function(cb) {

		var cbId = '';

		if(!!cb) {
			cbId = this.uniqid();
			this.callbacks[cbId] = cb;
		}

		return cbId;

	}

})();