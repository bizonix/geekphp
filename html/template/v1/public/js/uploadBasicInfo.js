$(function(){
  //上传主图片
  var swfu1 = uploadPic("mainImg","mainBtnCancel1","mainImgProgress",getSkuName('-basic-G','id'),'rand',uploadMainSuccess);
  //上传属性图片
  var swfu2 = uploadPic("propImg","propBtnCancel1","propImgProgress",getSkuName('-basic-P','id'),'rand',uploadPropSuccess);
});
//上传图片核心方法
function uploadPic(button_placeholder_id,cancelButtonId,progressTarget,imageName,multiply,upload_success_handler){
	var companyId 	= getCompanyId();
	var type		= "*.jpg;*.gif;*.png;*.JPG";
	var size		= "1 MB";
	var randStr 	= '';
	if(multiply == 'rand'){
		randStr = "/rand";
	}
	var settings1 = {
		flash_url : "http://source.huanhuan365.com/js/swfupload/swfupload/swfupload.swf",
		upload_url: "http://source.huanhuan365.com/upload/"+companyId+"/"+imageName+randStr,
		post_params: {
		},
		file_size_limit : size,
		file_types : type,
		file_types_description : type,
		file_upload_limit : 12,  //配置上传个数
		file_queue_limit : 0,
		custom_settings : {
			progressTarget : progressTarget,
			cancelButtonId : cancelButtonId
		},
		debug: false,
	
		// Button settings
		button_image_url: "http://source.huanhuan365.com/js/swfupload/images/TestImageNoText_65x29.png",
		button_width: "65",
		button_height: "29",
		button_placeholder_id: button_placeholder_id,
		button_text: '<span class="theFont">浏览</span>',
		button_text_style: ".theFont { font-size: 16; }",
		button_text_left_padding: 12,
		button_text_top_padding: 3,
		
		file_queued_handler : fileQueued,
		file_queue_error_handler : fileQueueError,
		file_dialog_complete_handler : fileDialogComplete,
		upload_start_handler : uploadStart,
		upload_progress_handler : uploadProgress,
		upload_error_handler : uploadError,
		upload_success_handler : upload_success_handler,
		upload_complete_handler : uploadComplete,
		queue_complete_handler : queueComplete	
	};
	return new SWFUpload(settings1);
}


function fileQueued(){
		try {
			var progress = new FileProgress(file, this.customSettings.progressTarget);
			progress.setStatus("正在等待...");
			progress.toggleCancel(true, this);

		} catch (ex) {
			this.debug(ex);
		}
}

function fileQueueError(file, errorCode, message) {
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			asyncbox.tips("您正在上传的文件队列过多.\n" + (message === 0 ? "您已达到上传限制" : "您最多能选择 " + (message > 1 ? "上传 " + message + " 文件." : "一个文件.")),'alert',1200);
			return;
		}

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			progress.setStatus("文件尺寸过大.");
			this.debug("错误代码: 文件尺寸过大, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			progress.setStatus("无法上传零字节文件.");
			this.debug("错误代码: 零字节文件, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			progress.setStatus("不支持的文件类型.");
			this.debug("错误代码: 不支持的文件类型, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		default:
			if (file !== null) {
				progress.setStatus("未处理的错误");
			}
			this.debug("错误代码: " + errorCode + ", 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		if (numFilesSelected > 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = false;
		}
		
		/* I want auto start the upload and I can do that here */
		this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}

function uploadStart(file) {
	if(!getCompanyId()){
		alert("未获取到团体ID，请选创建团体！");
		this.cancelQueue();
		return false;
	}
	if(!getSkuName('-basic','id')){
		alert('为获取到产品ID');
		this.cancelQueue();
		return false;
	}
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("正在上传...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {}
	return true;
	
}

function uploadProgress(file, bytesLoaded, bytesTotal) {
	try {
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setProgress(percent);
		progress.setStatus("正在上传...");
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadMainSuccess(file, serverData) {//后台文件的回调函数
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		var data 	= JSON.parse(serverData);
		if(data.errCode == "200"){
			progress.setStatus("上传成功");
			var imageUrl = "http://image.huanhuan365.com/tmpImg/"+getCompanyId()+'/'+data.data;
			var str = '<div role-mainImg style="margin:5px;display:inline-block;"><img role-remove onclick="deleteImg(this);" style="position: absolute;" src="/images/remove.png"/><a target="_blank" href="'+imageUrl+'"><img style="max-width:200px;max-height:100px;" src="'+imageUrl+'" /></a><p role-imgName>'+data.data+'</p><input type="hidden" name="main_images[]" value="'+imageUrl+'" /></div>';
			$('div[role-mainImgDiv]').append(str);
		}else{
			progress.setStatus("图片上传失败！");
		}
		progress.toggleCancel(false);
		
	} catch (ex) {
		this.debug(ex);
	}
}
function uploadPropSuccess(file, serverData) {//后台文件的回调函数
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		var data 	= JSON.parse(serverData);
		if(data.errCode == "200"){
			progress.setStatus("上传成功");
			var imageUrl = "http://image.huanhuan365.com/tmpImg/"+getCompanyId()+'/'+data.data;
			var str = '<div role-propImg style="margin:5px;display:inline-block;"><img role-remove onclick="deleteImg(this);" style="position: absolute;" src="/images/remove.png"/><a target="_blank" href="'+imageUrl+'"><img style="max-width:200px;max-height:100px;" src="'+imageUrl+'" /></a><p role-imgName>'+data.data+'</p><input type="hidden" name="extra_images[]" value="'+imageUrl+'" /></div>';
			$('div[role-propImgDiv]').append(str);
		}else{
			progress.setStatus("图片上传失败！");
		}
		progress.toggleCancel(false);
		
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadError(file, errorCode, message) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);
		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			progress.setStatus("上传错误: " + message);
			this.debug("错误代码: HTTP错误, 文件名: " + file.name + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			progress.setStatus("上传失败");
			this.debug("错误代码: 上传失败, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			progress.setStatus("服务器 (IO) 错误");
			this.debug("错误代码: IO 错误, 文件名: " + file.name + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			progress.setStatus("安全错误");
			this.debug("错误代码: 安全错误, 文件名: " + file.name + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			progress.setStatus("超出上传限制.");
			this.debug("错误代码: 超出上传限制, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			progress.setStatus("无法验证.  跳过上传.");
			this.debug("错误代码: 文件验证失败, 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			// If there aren't any files left (they were all cancelled) disable the cancel button
			if (this.getStats().files_queued === 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			progress.setStatus("取消");
			progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			progress.setStatus("停止");
			break;
		default:
			progress.setStatus("未处理的错误: " + errorCode);
			this.debug("错误代码: " + errorCode + ", 文件名: " + file.name + ", 文件尺寸: " + file.size + ", 信息: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}
function uploadComplete(file) {
	if (this.getStats().files_queued === 0) {
		document.getElementById(this.customSettings.cancelButtonId).disabled = true;
	}
}

// This event comes from the Queue Plugin
function queueComplete(numFilesUploaded) {
//	var status = document.getElementById("divStatus");
//	status.innerHTML = numFilesUploaded + " 个文件" + (numFilesUploaded === 1 ? "" : "s") + "已上传.";
}

function getPHPSESSID(){
	return $.trim($("input[name='PHPSESSID']").val());
}
function getCompanyId(){
	return $.trim($("input[name='company_id']").val());
}
function getSkuName(fix,findName){
	var sku = $.trim($("input[name="+findName+"]").val());
	if(sku){
		return $.trim(sku+fix);
	}
	return false
}

function deleteImg(obj){
	$(obj).parents('div[role-mainImg]').remove();
}
