var FormValidation = function () {
	var handleValidation1 = function() {
	    // for more info visit the official plugin documentation: 
	        // http://docs.jquery.com/Plugins/Validation
	
	        var form1 		= $('#editTemplateForm');
	        var error1 		= $('.alert-error', form1);
	        var success1 	= $('.alert-success', form1);
	        var validateRes = form1.validate({
	            errorElement: 'span', //default input error message container
	            errorClass: 'help-inline', // default input error message class
	            focusInvalid: false, // do not focus the last invalid input
//	            ignore: "",
	            onsubmit:true,
	            onkeyup :true,
	            rules: {
	            	"tp_name": {
	            		required: true,
	            		rangelength: [5,25],
	            		remote: {
	                        type: "post",
	                        url: "/template/checkTemplateIsExist",
	                        data: {
	                        	tp_name: function() {
	                                return $("input[name=tp_name]").val();
	                            }
	                        },
	                        datatype: "json",
	                        dataFilter: function(ret, type) {
	                            if (ret == false) return true;
	                            else return false;
	                        }
	                    }
	                },
	                "account[]": {
	                	required: true,
	                	minlength: 1,
	                },
	                "parent_sku": {
	                    required: true,
	                    rangelength: [3,25],
	                },
	                "name": {
	                    required: true,
	                    rangelength: [10,150],
	                },
	                "tags": {
	                    required: true,
	                },
	                "comVar[sku][]": {
	                	required: true,
	                },
	                "comVar[inventory][]": {
	                	required: true,
	                },
	                "comVar[price][]": {
	                	required: true,
	                },
	                "comVar[shipping][]": {
	                	required: true,
	                },
	                "description": {
	                	required: true,
	                }
	            },
	            messages: {
	            	"tp_name": {
	            		required: '范本名称为必填项',
	            		rangelength: '范本名称只能为5-25个字符之间',
	            		remote: '该范本已经存在，请更换范本名称'
	            	},
	            	"account[]": {
	            		required: '至少选择一个账号',
	            	},
	            	"parent_sku": {
	            		required: "主料号SPU必填项",
	            		rangelength: '3-25个字符之间',
	                },
	                "name": {
	                    required: "标题必填项",
	                    rangelength: "标题在10-150字符之间",
	                },
	                "tags": {
	                    required: "至少输入一组标签",
	                },
	                "comVar[sku][]": {
	                	required: "缺失必填项子料号sku",
	                },
	                "comVar[inventory][]": {
	                	required: "缺失必填项库存inventory",
	                },
	                "comVar[price][]": {
	                	required: "缺失必填项价格price",
	                },
	                "comVar[shipping][]": {
	                	required: "缺失必填项运费shipping",
	                },
	                "main_images[]": {
	                	required: "至少要上传一张主图",
	                },
	                "description": {
	                	required: "描述信息不能为空",
	                }
	            },
	
	            invalidHandler: function (event, validator) { //display error alert on form submit   
	                success1.hide();
	                error1.show();
	                FormValidation.scrollTo(error1, -200);
	            },
	
	            highlight: function (element) { // hightlight error inputs
	                $(element)
	                    .closest('.help-inline').removeClass('ok'); // display OK icon
	                $(element)
	                    .closest('.control-group').removeClass('success').addClass('error'); // set error class to the control group
	            },
	
	            unhighlight: function (element) { // revert the change done by hightlight
	                $(element).closest('.control-group').removeClass('error'); // set error class to the control group
	            },
	            success: function (label) {
	                label.addClass('valid').addClass('help-inline ok') // mark the current input as valid and display OK icon
	                .closest('.control-group').removeClass('error').addClass('success'); // set success class to the control group
	            },
	            errorPlacement: function(error, element) {
	                if ( element.is(":radio") ) return false;
	                else if ( element.is(":checkbox") ) error.appendTo ( $('#checkbox-show'));
	                else{
	                	error.appendTo(element.parent());
	                }
	            },
	
	            submitHandler: function (form) {
	                success1.show();
	                error1.hide();
	                if($('form').data('submitBtn') == 'saveBtn'){
	                	//保存
	                	submitSaveData();
	                }else if($('form').data('submitBtn') == 'listingBtn'){
	                	submitListingData();
	                }else if($('form').data('submitBtn') == 'updateListingBtn'){
	                	submitUpdateListing();
	                }
	            }
	        });
	}
	return {
	    //main function to initiate the module
	    init: function () {
	
	        return handleValidation1();
	
	    },
	
	// wrapper function to scroll to an element
	    scrollTo: function (el, offeset) {
	        pos = el ? el.offset().top : 0;
	        jQuery('html,body').animate({
	                scrollTop: pos + (offeset ? offeset : 0)
	            }, 'slow');
	    }
	
	};
}();