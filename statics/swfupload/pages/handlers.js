function preLoad() {
    if (!this.support.loading) {
        alert("You need the Flash Player 9.028 or above to use SWFUpload.");
        return false;
    }
}
function loadFailed() {
    alert("Something went wrong while loading SWFUpload. If this were a real application we'd clean up and then give you an alternative");
}

function fileQueueError(file, errorCode, message) {
    //console.log(file, errorCode, message);
    try {

        var errorName = '';
        switch (errorCode) {
			case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
			 errorName = "上传文件数量超出限制";
			 break;
            case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
                errorName = "上传文件内容为空";
                break;
            case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
                errorName = "上传文件大小超出限制";
                break;
            case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
                errorName = "无效文件类型";
                break;
            default:
                //alert(errorCode);
                break;
        }
        if(errorName != ''){
            alert(errorName);
            return;
        }



    } catch (ex) {
        this.debug(ex);
    }

}

function fileDialogComplete(numFilesSelected, numFilesQueued) {

    try {
		if(numFilesSelected >0 && numFilesSelected <= this.settings.file_queue_limit && numFilesQueued <= this.settings.file_upload_limit){
			loading = '<div class="pic_loading" style="float:left;height:150px;margin-top:10px;padding-top:75px"><img src="'+statics_url+'assets/global/img/loading-spinner-grey.gif" alt="" class="loading">' +
				'                <span> &nbsp;&nbsp;Uploading... </span></div>';
			$("#thumbnails").html(loading);
        }

        if (numFilesQueued > 0) {
            this.startResizedUpload(this.getFile(0).ID, 600, 450, SWFUpload.RESIZE_ENCODING.JPEG, 100);
        }
    } catch (ex) {
        this.debug(ex);
    }
}

function uploadProgress(file, bytesLoaded) {

    try {
		/*		var percent = Math.ceil((bytesLoaded / file.size) * 100);

		 var progress = new FileProgress(file,  this.customSettings.upload_target);
		 progress.setProgress(percent);
		 progress.setStatus("Uploading...");
		 progress.toggleCancel(true, this);*/

    } catch (ex) {
        this.debug(ex);
    }
}

function uploadSuccess(file, serverData) {
    try {

        //var progress = new FileProgress(file,  this.customSettings.upload_target);

        json = $.parseJSON(serverData);
        //console.log(json);
        if (json.code == 200 && json.data.fileurl !='') {
            addImage(json.data.fileurl,this);

            //progress.setStatus("Upload Complete.");
            //progress.toggleCancel(false);
        }else{
            //addImage("images/error.gif");
            //progress.setStatus("Error.");
            //progress.toggleCancel(false);
            alert(json.msg);
        }

    } catch (ex) {
        this.debug(ex);
    }
}

function uploadComplete(file) {
    try {
		/*  I want the next upload to continue automatically so I'll call startUpload here */
        if (this.getStats().files_queued > 0) {
            this.startResizedUpload(this.getFile(0).ID, 600, 450, SWFUpload.RESIZE_ENCODING.JPEG, 100);
        } else {
            //var progress = new FileProgress(file,  this.customSettings.upload_target);
            //progress.setComplete();
            //progress.setStatus("All images received.");
            //progress.toggleCancel(false);
        }
    } catch (ex) {
        this.debug(ex);
    }
}

function uploadError(file, errorCode, message) {
    var imageName =  "error.gif";
    var progress;
    try {
        alert(message);
    } catch (ex3) {
        this.debug(ex3);
    }

}


function addImage(src,swfu) {
    $("#thumbnails .pic_loading").remove();
    pic = '<div class="pic" style="float:left"><img src="'+src+'" style="margin: 10px 10px 10px 0; height: 150px; opacity: 1;"><div style="text-align: center;"><a class="remove_pic" href="javascript:void(0)">移除</a></div></div>';
    $("#thumbnails").html(pic);
    $(".remove_pic").on('click',function(){
        $(this).closest('.pic').remove();
        //console.log(that.getStats());
        //successful_uploads = swfu.getStats().successful_uploads
        swfu.setStats({"successful_uploads":swfu.getStats().successful_uploads -1});
    });
}

function fadeIn(element, opacity) {
    var reduceOpacityBy = 5;
    var rate = 30;	// 15 fps


    if (opacity < 100) {
        opacity += reduceOpacityBy;
        if (opacity > 100) {
            opacity = 100;
        }

        if (element.filters) {
            try {
                element.filters.item("DXImageTransform.Microsoft.Alpha").opacity = opacity;
            } catch (e) {
                // If it is not set initially, the browser will throw an error.  This will set it if it is not set yet.
                element.style.filter = 'progid:DXImageTransform.Microsoft.Alpha(opacity=' + opacity + ')';
            }
        } else {
            element.style.opacity = opacity / 100;
        }
    }

    if (opacity < 100) {
        setTimeout(function () {
            fadeIn(element, opacity);
        }, rate);
    }
}



/* ******************************************
 *	FileProgress Object
 *	Control object for displaying file info
 * ****************************************** */

function FileProgress(file, targetID) {
    this.fileProgressID = "divFileProgress";

    this.fileProgressWrapper = document.getElementById(this.fileProgressID);
    if (!this.fileProgressWrapper) {
        this.fileProgressWrapper = document.createElement("div");
        this.fileProgressWrapper.className = "progressWrapper";
        this.fileProgressWrapper.id = this.fileProgressID;

        this.fileProgressElement = document.createElement("div");
        this.fileProgressElement.className = "progressContainer";

        var progressCancel = document.createElement("a");
        progressCancel.className = "progressCancel";
        progressCancel.href = "#";
        progressCancel.style.visibility = "hidden";
        progressCancel.appendChild(document.createTextNode(" "));

        var progressText = document.createElement("div");
        progressText.className = "progressName";
        progressText.appendChild(document.createTextNode(file.name));

        var progressBar = document.createElement("div");
        progressBar.className = "progressBarInProgress";

        var progressStatus = document.createElement("div");
        progressStatus.className = "progressBarStatus";
        progressStatus.innerHTML = "&nbsp;";

        this.fileProgressElement.appendChild(progressCancel);
        this.fileProgressElement.appendChild(progressText);
        this.fileProgressElement.appendChild(progressStatus);
        this.fileProgressElement.appendChild(progressBar);

        this.fileProgressWrapper.appendChild(this.fileProgressElement);

        document.getElementById(targetID).appendChild(this.fileProgressWrapper);
        fadeIn(this.fileProgressWrapper, 0);

    } else {
        this.fileProgressElement = this.fileProgressWrapper.firstChild;
        this.fileProgressElement.childNodes[1].firstChild.nodeValue = file.name;
    }

    this.height = this.fileProgressWrapper.offsetHeight;

}
FileProgress.prototype.setProgress = function (percentage) {
    this.fileProgressElement.className = "progressContainer green";
    this.fileProgressElement.childNodes[3].className = "progressBarInProgress";
    this.fileProgressElement.childNodes[3].style.width = percentage + "%";
};
FileProgress.prototype.setComplete = function () {
    this.fileProgressElement.className = "progressContainer blue";
    this.fileProgressElement.childNodes[3].className = "progressBarComplete";
    this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setError = function () {
    this.fileProgressElement.className = "progressContainer red";
    this.fileProgressElement.childNodes[3].className = "progressBarError";
    this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setCancelled = function () {
    this.fileProgressElement.className = "progressContainer";
    this.fileProgressElement.childNodes[3].className = "progressBarError";
    this.fileProgressElement.childNodes[3].style.width = "";

};
FileProgress.prototype.setStatus = function (status) {
    this.fileProgressElement.childNodes[2].innerHTML = status;
};

FileProgress.prototype.toggleCancel = function (show, swfuploadInstance) {
    this.fileProgressElement.childNodes[0].style.visibility = show ? "visible" : "hidden";
    if (swfuploadInstance) {
        var fileID = this.fileProgressID;
        this.fileProgressElement.childNodes[0].onclick = function () {
            swfuploadInstance.cancelUpload(fileID);
            return false;
        };
    }
};
