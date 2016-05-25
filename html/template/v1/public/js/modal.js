function setModal(id,title,content){
	var str = '';
	str = '<div class="modal fade" style="display:none;" id="'+id+'" tabindex="-1" role="dialog" aria-hidden="true">';
    str += '<div class="modal-dialog">';
    str += '<div class="modal-content">';
    str += '<div class="modal-header">';
    str += '<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only"></span></button>';
    str += '<h4 class="modal-title" id="myModalLabel">'+title+'</h4>';
    str += '</div>';
    str += '<div class="modal-body">';
    str += '<div class="form-group">';
    str += content;
    str += '</div>';
    str += '</div>';
    str += '<div class="modal-footer">';
    str += '<button name="footer-cancel" type="button" class="btn btn-default" data-dismiss="modal">取消</button>';
    str += '<button name="footer-submit" type="button" class="btn btn-primary">确认</button>';
    str += '</div>';
    str += '</div>';
    str += '</div>';
    str += '</div>';
	if($(id).length == 0){
		$("body").append(str);
	}
}