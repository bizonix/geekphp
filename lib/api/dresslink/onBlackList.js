$(function(){
	var status = $("#status").val();
	if(status != ''){
		alert(status);
		window.location.href = "index.php?mod=omBlackList&act=getOmBlackList";
	}
	
	
    $(".update").click(function(){
        var id = $(this).attr("pid");
        window.location.href = "index.php?mod=omBlackList&act=updateBlackList&id="+id;
	});
    
	$('#all-select').click(function(){
		if(($('#all-select').attr('checked')	==	'checked')&&($('#inverse-select').attr('checked')	==	'checked')){
			$('#inverse-select').attr('checked',false);
		}
		select_all_blackList('all-select',1,'input[name="account[]"]');
	});

	$('#inverse-select').click(function(){
		if(($('#all-select').attr('checked')	==	'checked')&&($('#inverse-select').attr('checked')	==	'checked')){
			$('#all-select').attr('checked',false);
		}
		select_all_blackList('inverse-select',0,'input[name="account[]"]');
	});
    $(".delete").click(function(){
        var id = $(this).attr("pid");
        if($.trim(id) && confirm('确定要删除该平台记录？')){
             window.location.href = "index.php?mod=omBlackList&act=deleteBlackList&id="+id;
        }
        
    });
	
    $("#back").click(function(){
        history.back();
    });
	
    $("#back").click(function(){
        window.location.href = "index.php?mod=omBlackList&act=getOmBlackList";
		
    });

});

function onchangeSite(){
	var platformId	=	$("#platformId").val();
	var htmlStr	=	'';
	var	obj;
	$.ajax(
		{
			type: 'get',
			url: 'json.php?act=index&mod=omBlackList&jsonp=1',
			dataType : 'json',
			data        :{"platformId":platformId},
			success : function (data){
				if(data.errCode == 300){
					alertify.error(data.errMsg);
				}else{
					for(obj in data.data){
						htmlStr	+=	'<input value="'+data.data[obj].id+'" type="checkbox" data-val="'+data.data[obj].account+'" name="account[]" checked="checked">'+data.data[obj].account;
					}
					$("#selectPlatformAccount").html(htmlStr);
				}
			}
		}
	);
}

function onchangModify(){
    var platformId = $('#platformId').val();
	var id	=	$("#id").val();
	window.location.href = "index.php?mod=omBlackList&act=updateBlackList&id="+id+"&platformId="+platformId;
}


function select_all_blackList(id,type,selector,callback){
	var ckbutton_cur_checked = $('#'+id).attr('checked'); 
	$(selector).each(function(){
		if(this.disabled) return true;
		var self = $(this);
		if(type==1){
			if(ckbutton_cur_checked == undefined) ckbutton_cur_checked = false;
			self.attr('checked',ckbutton_cur_checked);
		}
		else{
			self.attr('checked',!self[0].checked);
		}
	});
	if(type == 1){
		$('#sku-inverse').attr('checked',false);
	}else{
		$('#sku-all').attr('checked',false);
	}

	try{
		callback.call();
	}
	catch(e){}
}

