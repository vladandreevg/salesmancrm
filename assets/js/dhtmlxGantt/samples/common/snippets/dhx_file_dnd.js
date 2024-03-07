function fileDragAndDrop(){
	var overlay = null,
		uid = Date.now();

	function callListeners(file){
		for(var i in this.listeners){
			if(this.listeners[i])
				this.listeners[i](file);
		}
	}

	var showHighlight = false,
		timeout = 0;

	return {
		root: null,
		listeners: {},
		onDrop: function(listener){
			var id = uid++;
			this.listeners[id] = listener;
			return id;
		},
		removeListener: function(id){
			delete this.listeners[id];
		},

		init: function(div){

			this.root = div;

			div.addEventListener("dragover", gantt.bind(function(event){
				event.preventDefault && event.preventDefault();
				showHighlight = true;
				this.showHover();
			}, this), false);

			div.addEventListener("dragenter", gantt.bind(function(event){
				event.preventDefault && event.preventDefault();
				showHighlight = true;
				this.showHover();
			}, this), false);

			div.addEventListener("dragleave", gantt.bind(function(event){
				showHighlight = false;
				clearTimeout( timeout );
				timeout = setTimeout( gantt.bind(function(){
					if( !showHighlight ){ this.hideOverlay(); }
				}, this), 200 );
			}, this), false);

			div.addEventListener("dragend", gantt.bind(function(event){
				this.hideOverlay();
				showHighlight = false;
			}, this), false);

			div.addEventListener("drop", gantt.bind(function(event){
				event.preventDefault && event.preventDefault();
				showHighlight = false;
				this.hideOverlay();

				var files = event.dataTransfer.files;

				callListeners.call(this, files[0]);
				return false;
			}, this), false);
		},

		hideOverlay: function(){
			if(!overlay) return;
			overlay.parentNode.removeChild(overlay);
			overlay = null;
		},

		showHover: function showFileHover(){
			this.showOverlay('<div class="gantt-file-hover-content-upload-image"></div>' +
				'<div class="gantt-file-hover-content-upload-message">Drop MPP or XML file into Gantt</div>');
		},

		showUpload: function showFileInProgress(){
			this.showOverlay('<div class="gantt-file-upload-spinner"><div class="gantt-file-upload-spinner-inner"></div></div>' +
				'<div class="gantt-file-hover-content-upload-message">Loading&hellip;</div>');
		},

		showOverlay: function showOverlay(innerHTML){
			if(!this.root) return;
			if(overlay) return;

			overlay = document.createElement("div");
			overlay.className = "gantt-file-hover";

			overlay.innerHTML = '<div class="gantt-file-hover-inner">' +
				'<div class="gantt-file-hover-content-pending">' +
				innerHTML +
				'</div>' +
				'</div>';
			this.root.appendChild(overlay);
		}
	};
}