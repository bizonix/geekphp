$(function() {
  $(".uniform_on").uniform();
  $('#addTemplateForm select').chosen();

  $("li[name='chooseCategory']").on("click",function(){
    clickCategory();
  });
  $("#chooseCategoryDialog [name='chooseCategory']").on("click",function(){
    //var data = [];
    var categoryStr = [];
    var lastPid = 0;
    $("[name='categoryList']").children('td').each(function(i){
      //data[i]  = {"value":$(this).children('select').find("option:selected").text(),"key":$(this).children('select').val()};
      if($(this).children('select').val()!==null){
        categoryStr.push($(this).children('select').find("option:selected").text());
        lastPid = $(this).children('select').val();
      }
    });
    if(lastPid!=0){
      $("li[name='chooseCategory']").next('.help-inline').text(categoryStr.join(" > "));
      $("li[name='chooseCategory']").prev('[name="good_category"]').val(lastPid);
    }
    $('#chooseCategoryDialog').modal('hide');
  });
  $("[name='addVariationAttributeRow']").on('click',function(){
    var bodyObj = $("[name='variationAttributeBody']");
    var html = bodyObj.children('tr')[0].outerHTML;
    bodyObj.append(html);
    bodyObj.children('tr').last().children('input').val('');
  });
  $("[name='addVariationAttribute']").on('click',function(){
    var tableObj = $("[name='variationAttributeTable'] tr");
    if($(tableObj[0]).children().length<7){
      tableObj.each(function(index, el) {
        if(index==0){
          $(this).children().last().prev().after(
            '<th>'
              +'<span class="icon-remove" onclick="delVariationAttribute(this)" style="cursor:pointer;"></span>'
              +'<input style="width:50px;" type="text" />'
              +'<span class="dropdown">'
              +'<span style="cursor:pointer;" data-toggle="dropdown" class="dropdown">>></span>'
              +'<ul class="dropdown-menu">'
                +'<li onclick="chooseAttribute(this)">'
                  +'<a data-toggle="tab" href="#" attributeValue="Size">Size</a>'
                +'</li>'
                +'<li onclick="chooseAttribute(this)">'
                  +'<a data-toggle="tab" href="#" attributeValue="Main Colour">Main Colour</a>'
                +'</li>'
                +'<li onclick="chooseAttribute(this)">'
                  +'<a data-toggle="tab" href="#" attributeValue="Material">Material</a>'
                +'</li>'
                +'<li onclick="chooseAttribute(this)">'
                  +'<a data-toggle="tab" href="#" attributeValue="Length">Length</a>'
                +'</li>'
                +'<li onclick="chooseAttribute(this)">'
                  +'<a data-toggle="tab" href="#" attributeValue="Size Type">Size Type</a>'
                +'</li>'
              +'</ul>'
              +'</span>'
            +'</th>'
            );                          
        }else{
          $(this).children().last().prev().after('<td><input style="width:100px;" type="text" /></td>');
        }
      });
    }else{
      alert("最多只能添加这么多了");
    }
  });
  $("[name='variationAttributePic']").on('click',function(){
    var len = $("[name='variationAttributeHead'] th").length;
    var variationList = [];
    var html='';
    if(len>4){
      $("[name='variationAttributeBody'] tr").each(function(){
        tmp = $(this).children().eq(3).children('input').val();
        if($.inArray(tmp,variationList)==-1){
          variationList.push(tmp);
        }
      });
      for(var i in variationList){
        html += generatevariationAttributePicHtml(variationList[i]);
      }
      $("[name='variationAttributePicBody']").html(html);
    }else{
      alert("未设置属性");
    }
  });
  var swfu3 = uploadPic("localDetailPic","detailBtnCancel1","detailPicProgress",upload_success_handler_detail);
  var swfu2 = uploadPic("localMainPic","mainBtnCancel1","mainPicProgress",upload_success_handler_main);
  var swfu1 = uploadPic("localVariationPic","variationBtnCancel1","variationPicProgress",upload_success_handler);
  $("input[name='spu']").on("blur",function(){
    initVariationDialog();
    initMainDialog();
    initDetailDialog();
  });

  $("select[name='siteId']").on("change",function(){
    var siteId = $(this).val();
    initShipService(siteId);
    changeAccount(siteId);
  });
  var siteId = $("select[name='siteId']").val();
  initShipService(siteId);
  changeAccount(siteId);
  $( '#decText' ).ckeditor({width:'98%', height: '150px'});
});

function changeAccount(siteId){
   $.ajax({
    type  : "POST",
    async : false,
    url   : '/ebayTemplate/getAccountBySiteId/',
    dataType : "json",
    data : {"site_id":siteId},
    success : function(ret){
      if(ret.data!=''){
        var html = generateAccountHtml(ret.data,siteId);
        updateChoosePlugins($(".control-group .controls [name='account']"),html);
      }else{
        updateChoosePlugins($(".control-group .controls [name='account']"),'');
        alert_success("该站点没有对应的账号");
      }
    },
  });
}
function generateAccountHtml(data,siteId){
  var html ='';
  for(var k in data){
    html +='<option value="'+data[k]['shop_id']+'" class="form-control chzn-select option">'+data[k]['shop_account']+'</option>';
  }
  return html;
  
}
function updateChoosePlugins(obj,html){
  obj.html(html);
  obj.trigger("liszt:updated");
  obj.chosen();
}
function initShipService(siteId){
  $.ajax({
    type  : "POST",
    async : false,
    url   : '/ebayTemplate/getShipService/',
    dataType : "json",
    data : {"siteId":siteId},
    success : function(ret){
      if(ret.data!=''){
        var html = generateShipServiceHtml(ret,siteId);
        //$("[name='categoryList']").append(html);
      }
    },
  });
}
function generateShipServiceHtml(ret,siteId){
  if(ret.errCode != 200){
    alert(ret.errMsg);
    return;
  }
  var length  = ret.data.length;
  if(length == 0) return;
  ret = ret.data;
  var home  = ret['home'];
  var inter = ret['inter'];

  var html  = "<option value=\"\">-- 选择 --</option>";
  for(var j in home){
    html  +=  "<optgroup label=\""+j+"\">";
    var shipping  = home[j];
    for(var i in shipping){
      var name  = "";
      if(typeof(shipping[i]['ShippingTimeMax']) != "undefined" && shipping[i]['ShippingTimeMin'] != ""){
        name= shipping[i]['Description']+"("+shipping[i]['ShippingTimeMin']+" to "+ shipping[i]['ShippingTimeMax']+" days)";
      }else{
        name= shipping[i]['Description'];
      }
      html  +=  "<option value=\""+shipping[i]['ShippingService']+"\">"+name+"</option>";
    }
    html  +=  "</optgroup>";
  }
  $("#idShippingService").html(html);
  $("#idShippingService").trigger("liszt:updated");
  $("#idShippingService").chosen();
  // if(siteId == 216 || siteId == 211 || siteId == 207){ 
  //   $("#dlShippingInt5").css("display","none");
  // }else{
  //   $("#dlShippingInt5").css("display","block");

    var html  = "<option value=\"\">-- 选择 --</option>";
    for(var j in inter){
      html  +=  "<optgroup label=\""+j+"\">";
      var shipping  = inter[j];
      for(var i in shipping){
        var name  = "";
        if(typeof(shipping[i]['ShippingTimeMax']) != "undefined" && shipping[i]['ShippingTimeMin'] != ""){
          name= shipping[i]['Description']+"("+shipping[i]['ShippingTimeMin']+" to "+ shipping[i]['ShippingTimeMax']+" days)";
        }else{
          name= shipping[i]['Description'];
        }
        // if(inter_shipping == shipping[i]['ShippingService']){
        //   html  +=  "<option selected='selected' value=\""+shipping[i]['ShippingService']+"\">"+name+"</option>";
        // }else{
          html  +=  "<option value=\""+shipping[i]['ShippingService']+"\">"+name+"</option>";
        //}
      } 
      html  +=  "</optgroup>";
    }
    $("#InternationalShippingService").html(html);
    $("#InternationalShippingService").trigger("liszt:updated");
    $("#InternationalShippingService").chosen();
  //}
}
function setShippingExclusion(){
  var siteId =  $('select[name="siteId"]').val();
  if($("#ddlExclusionListType").val() != 2) return;

  $.ajax({
    type  : "POST",
    async : false,
    url   : '/ebayTemplate/getShippingExclusion/',
    dataType : "json",
    data : {"siteId":siteId},
    success : function(ret){
      if(ret.data!=''){
        var html = generateShippingExclusionHtml(ret);
        $("#chooseShippingExclusionDialog [name='shippingExclusionChoose']").html(html);
        $("#chooseShippingExclusionDialog").modal("show");
      }
    },
  });
}
function generateShippingExclusionHtml(ret){
  if(ret.errCode != 200){
    alert(ret.errMsg);
    return;
  }
  var dat = ret.data;
  var html  = "";
  var index = 0;
  var hidden_val  = $("#ExcludeShippingList").val();
  var exist_value = new Array();
  if(hidden_val != ""){
    exist_value = hidden_val.split(",");
  }
  for(var i in dat){
    var label = i;
    var detail  = dat[i];
    if(label == "Worldwide") continue;
    var length  = detail.length;
    var label_is_checked  = false;
    var label_html  = "";
    for(var k=0; k<exist_value.length; k++){
      if(exist_value[k] == label){ 
        label_is_checked = true;
        label_html  = "checked=\"checked\"";
      }
    }
    html  +=  "<div style=\"float: left;width: 780px;\"><label>"+label
          +"<input type=\"checkbox\" "+label_html+" value=\""+label+"\" id=\"ExcludeLocationArea_"+index+"\" onclick=\"setExcludeLocation('"+index+"','all')\">"
          +"<input type=\"hidden\" value=\""+length+"\" id=\"ExcludeLocation_length_"+index+"\"> "
          +"</label><ul>";
    for(var j=0; j<detail.length; j++){
      var value = detail[j]['Location'];
      var detail_html = "";
      if(label_is_checked){ //全选
        detail_html = "checked=\"checked\"";
      }else{  //单独
        for(var k=0; k<exist_value.length; k++){
          if(exist_value[k] == value){ 
            detail_html = "checked=\"checked\"";
          }
        }
      }
      var name  = detail[j]['Description'];
      html  +=  "<li style=\"list-style-type: none;float: left;width: 250px\"><input type=\"checkbox\" "+detail_html+" onclick=\"setExcludeLocation('"+index+"','"+j+"')\" value=\""+value+"\" name=\"ExcludeLocation\" id=\"ExcludeLocation_"+index+"_"+j+"\">"+name+"</li>";
    }
    html  +=  "</ul></div>";
    index++;
  }
  return html;
}
function setExcludeLocation(id, inner){
  var length    = $("#ExcludeLocation_length_"+id).val();
  if($("#ExcludeShippingDetail").html() != ""){
    var old_list  = $("#ExcludeShippingDetail").html().split(",");
  }else{
    var old_list  = new Array();
  }
  var empty_list  = new Array();
  var new_list  = new Array();
  var exist_area  = false;
  var area_value  = $("#ExcludeLocationArea_"+id).val();
  //全选
  if(inner == "all"){
    //勾中
    if($("#ExcludeLocationArea_"+id).is(':checked')){ 
      for(var i = 0; i < length; i++){
        //国家全勾
        empty_list.push($("#ExcludeLocation_"+id+"_"+i).val());
        $("#ExcludeLocation_"+id+"_"+i).prop("checked","checked");
      }
      
      for(var j=0; j < old_list.length; j++){
        if(old_list[j] == area_value) exist_area = true;
        var need_add  = true;
        for(var k=0; k < empty_list.length; k++){
          if(empty_list[k] == old_list[j]) need_add = false;
        }
        if(need_add) new_list.push(old_list[j]);
      }
      if(!exist_area) new_list.push(area_value);
      
    }else{
      for(var i = 0; i < length; i++){
        empty_list.push($("#ExcludeLocation_"+id+"_"+i).val());
        $("#ExcludeLocation_"+id+"_"+i).removeAttr("checked");
      }
      for(var j=0; j < old_list.length; j++){
        
        var need_add  = true;
        for(var k=0; k < empty_list.length; k++){
          if(empty_list[k] == old_list[j]) need_add = false;
        }
        if(old_list[j] == area_value) need_add = false;
        if(need_add) new_list.push(old_list[j]);
      }
      
    }
    $("#ExcludeShippingList").val(new_list.join(","));
    $("#ExcludeShippingDetail").html(new_list.join(","));
  }else{  
      //国家未勾中
      $("#ExcludeLocationArea_"+id).removeAttr("checked");
      var cur   = $("#ExcludeLocation_"+id+"_"+inner).val();
      var all_is_set  = false;  //是否全部已经勾上
      var set_num = 0;
      for(var i = 0; i < length; i++){
        empty_list.push($("#ExcludeLocation_"+id+"_"+i).val());
        if($("#ExcludeLocation_"+id+"_"+i).is(":checked")) set_num++;
      }
      if(set_num == length){ 
        $("#ExcludeLocationArea_"+id).prop("checked",true);
        all_is_set  = true;
      }
      if(all_is_set){
        //设置区域的勾选
        new_list.push(area_value);
        //取消区域内的国家
        for(var j=0; j < old_list.length; j++){ 
          var have_area_country = false;
          for(var i = 0; i < length; i++){
            var value_i = $("#ExcludeLocation_"+id+"_"+i).val();
            if(old_list[j] == value_i){
              have_area_country = true;
            }
          }
          if(!have_area_country)  new_list.push(old_list[j]);
        }
      }else{
        for(var j=0; j < old_list.length; j++){ 
          var need_add  = true;
          if(old_list[j] == area_value) need_add = false; //取消区域的勾中
          if(old_list[j] == cur) need_add = false;    //取消单个未勾中的国家
          if(need_add) new_list.push(old_list[j]);
        }
        for(var i = 0; i < length; i++){
          var value_i = $("#ExcludeLocation_"+id+"_"+i).val();
          if($("#ExcludeLocation_"+id+"_"+i).is(":checked")){
            var in_old_list = false;
            for(var j=0; j < old_list.length; j++){ 
              if(old_list[j] == value_i) in_old_list=true;
            }
            if(!in_old_list)  new_list.push(value_i);
          }
        }
      }
      $("#ExcludeShippingList").val(new_list.join(","));
      $("#ExcludeShippingDetail").html(new_list.join(","));
  }
}
function changeReturnPolicy(val){
  if(val == "ReturnsAccepted"){
    $("#ReturnAccept_id").css("display","block");
  }else{
    $("#ReturnAccept_id").css("display","none");
  }
}
function changeBuyerRequirement(val){
  if(val == "1"){
    $("#BuyerRequirementDetail").css("display","none");
  }else{
    $("#BuyerRequirementDetail").css("display","");
  }
}
function upload_success_handler(file, serverData){
  try {
    var progress = new FileProgress(file, this.customSettings.progressTarget);
    progress.setComplete();
    var data  = JSON.parse(serverData);
    if(data.errCode == "200"){
      progress.setStatus("上传成功");
      var str = 
      '<span>'+
        '<img src="'+data.data['url']+'" style="width:100px;height: 100px" onclick="chooseThisPic(this)">'+
      '</span>';
      $("[name='picLibChoose']").append(str);
    }else{
      progress.setStatus("图片上传失败！");
    }
    progress.toggleCancel(false);
    
  } catch (ex) {
    this.debug(ex);
  }
}
function upload_success_handler_detail(file, serverData){
  try {
    var progress = new FileProgress(file, this.customSettings.progressTarget);
    progress.setComplete();
    var data  = JSON.parse(serverData);
    if(data.errCode == "200"){
      progress.setStatus("上传成功");
      var str = 
      '<span>'+
        '<img src="'+data.data['url']+'" style="width:100px;height: 100px" onclick="chooseThisDetailPic(this)">'+
      '</span>';
      $("[name='detailPicLibChoose']").append(str);
    }else{
      progress.setStatus("图片上传失败！");
    }
    progress.toggleCancel(false);
    
  } catch (ex) {
    this.debug(ex);
  }
}

function upload_success_handler_main(file, serverData){
  try {
    var progress = new FileProgress(file, this.customSettings.progressTarget);
    progress.setComplete();
    var data  = JSON.parse(serverData);
    if(data.errCode == "200"){
      progress.setStatus("上传成功");
      var str = 
      '<span>'+
        '<img src="'+data.data['url']+'" style="width:100px;height: 100px" onclick="chooseThisMainPic(this)">'+
      '</span>';
      $("[name='mainPicLibChoose']").append(str);
    }else{
      progress.setStatus("图片上传失败！");
    }
    progress.toggleCancel(false);
    
  } catch (ex) {
    this.debug(ex);
  }
}
function initVariationDialog(){
  var spu = $("input[name='spu']").val();
  $.ajax({
    type  : "POST",
    async : true,
    url   : '/ebayTemplate/getVariationPicList/',
    dataType : "json",
    data : {"spu":spu},
    success : function(ret){
      if(ret.data!=''){
        var html = '';
        for(var i in ret.data){
          //html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this,'+index+')"></span>';
          html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this)"></span>';
        }
        $("[name='picLibChoose']").html(html);
      }
    },
  });
}
function initMainDialog(){
  var spu = $("input[name='spu']").val();
  $.ajax({
    type  : "POST",
    async : true,
    url   : '/ebayTemplate/getMainPicList/',
    dataType : "json",
    data : {"spu":spu},
    success : function(ret){
      if(ret.data!=''){
        var html = '';
        for(var i in ret.data){
          //html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this,'+index+')"></span>';
          html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisMainPic(this)"></span>';
        }
        $("[name='mainPicLibChoose']").html(html);
      }
    },
  });
}
function initDetailDialog(){
  var spu = $("input[name='spu']").val();
  $.ajax({
    type  : "POST",
    async : true,
    url   : '/ebayTemplate/getDetailPicList/',
    dataType : "json",
    data : {"spu":spu},
    success : function(ret){
      if(ret.data!=''){
        var html = '';
        for(var i in ret.data){
          //html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this,'+index+')"></span>';
          html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisDetailPic(this)"></span>';
        }
        $("[name='detailPicLibChoose']").html(html);
      }
    },
  });
}
function setVariationIndex(obj){
  var index = $(obj).parent("td").parent("tr").prevAll().length;
  $("#choosePicDialog").data("index",index);
  //var name = $(obj).prevAll('[name="picVariationName"]').text();
}
function initDialog(obj){
  var spu = $("input[name='spu']").val();
  var index = $(obj).parent("td").parent("tr").prevAll().length;
  $("#choosePicDialog").data("index",index);
  var name = $(obj).prevAll('[name="picVariationName"]').text();
  $.ajax({
    type  : "POST",
    async : false,
    url   : '/ebayTemplate/getVariationPicList/',
    dataType : "json",
    data : {"spu":spu},
    success : function(ret){
      if(ret.data!=''){
        var html = '';
        for(var i in ret.data){
          //html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this,'+index+')"></span>';
          html += '<span><img src="'+ret.data[i]+'" style="width:100px;height: 100px" onclick="chooseThisPic(this)"></span>';
        }
        $("[name='picLibChoose']").append(html);
      }
    },
  });
}
function chooseThisMainPic(obj){
  var url = $(obj).attr("src");
  var flag = false;
  var i=1;
  var max = 12;
  $("[name='mainPicBody']").children("span").children("input[name='mainPic']").each(function(index, el) {
    i++;
    if($(this).val()==url){
      flag = true;
    }
  });
  if(i>2){
    alert("最多只能选"+max+"张");
    return;
  }
  if(!flag){
    var html = 
      '<span>'+
        '<img src="'+url+'" style="width:100px;height: 100px" >'+
        '<input type="hidden" value="'+url+'" name="mainPic" />'+
      '</span>';
    $("[name='mainPicBody']").append(html);
  }else{
    alert("该图片已经选过了");
  }
}

function chooseThisDetailPic(obj){
  var url = $(obj).attr("src");
  var flag = false;
  var i=1;
  var max = 12;
  $("[name='detailPicBody']").children("span").children("input[name='detailPic']").each(function(index, el) {
    i++;
    if($(this).val()==url){
      flag = true;
    }
  });
  if(i>2){
    alert("最多只能选"+max+"张");
    return;
  }
  if(!flag){
    var html = 
      '<span>'+
        '<img src="'+url+'" style="width:100px;height: 100px">'+
        '<input type="hidden" value="'+url+'" name="detailPic" />'+
      '</span>';
    $("[name='detailPicBody']").append(html);
  }else{
    alert("该图片已经选过了");
  }
}
function chooseThisPic(obj){
  var index =  $("#choosePicDialog").data("index");
  var url = $(obj).attr("src");
  var insertInput =  $($("[name='variationAttributePicBody']").children("tr")[index]).children("td");
  $($("[name='variationAttributePicBody']").children("tr")[index]).children("td").children('input[type="hidden"]').val(url);
  $("#choosePicDialog").modal("hide"); 
}
function chooseAttribute(obj){
  var attributeValue = $(obj).children("a").text();
  //var status = $(obj).hasClass('active');
  var oldattributeValue = $(obj).parents(".dropdown").prev("input").val();
  if($(obj).siblings("li").children("a[attributeValue='"+oldattributeValue+"']").length>0){
    $(obj).siblings("li").children("a[attributeValue='"+oldattributeValue+"']").parent().removeClass('active');
  }
  //oldattributeValue = $(obj).parents(".dropdown").prev("input").val(attributeValue);
  if(existAttribute(attributeValue)&&oldattributeValue!=attributeValue){
    alert("已经存在了");
  }else{
    oldattributeValue = $(obj).parents(".dropdown").prev("input").val(attributeValue);
  }
}
function existAttribute(str){
  var status = false;
  $("[name='variationAttributeHead'] th").children("input").each(function(){
    var value = $(this).val();
    if(value&&value!=''&&value==str){
      status = true;
    }
  });
  return status;
}
function generatevariationAttributePicHtml(value){
  //var str = 
  if(value==''){
    return '';
  }
  var html= '<tr>'+
    '<td>'+
      '<img src="" style="width:100px;height: 100px">'+
    '</td>'+
    '<td>'+
      '<input type="hidden" value="" />'+
      '<span name="picVariationName">'+value+'</span><br / >'+
      '<li data-target="#choosePicDialog" name="uploadByHand" class="btn btn-info" data-toggle="modal" onclick="setVariationIndex(this)">图片选择</li><br />'+
      '<li class="btn btn-info" name="delUpload">删除</li>'+
    '</td>'+
  '</tr>';
  return html;
}

function delVariationAttributeRow(obj){
  if($("[name='variationAttributeBody']").children('tr').length>1){
    $(obj).parents("tr").remove();
  }else{
    var tip = $(obj).parents("table").next("span");
    tip.text("至少要一种多属性");
    setInterval(function(){tip.text("");},2000);
  }
}
function delVariationAttribute(obj){
 var delIndex = $(obj).parents("th").prevAll().length;
 var tableObj = $("[name='variationAttributeTable'] tr");
    tableObj.each(function(index, el) {
      $($(this).children()[delIndex]).remove();
    });
    
}
function clickCategory(obj){
    var siteId =  $('#addTemplateForm select[name="siteId"]').val();
    if(obj!=undefined){
      var pid = $(obj).val();
      var tdIndex = $(obj).parents("td").prevAll().length;
    }else{
      var pid = 0;
      var tdIndex = 0;
    }
    var length = $("[name='categoryList']").children('td').length;
    var distance = length-tdIndex;
    while(distance>1){
      distance=distance-1;
      $($("[name='categoryList']").children('td')[tdIndex+distance]).remove();
    }
    $.ajax({
      type  : "POST",
      async : false,
      url   : '/ebayTemplate/getCategoryInfoBySiteIdAndPid/',
      dataType : "json",
      data : {"siteId":siteId,"pid":pid},
      success : function(ret){
        if(ret.data!=''){
          var html = generateHtml(ret.data);
          $("[name='categoryList']").append(html);
        }
      },
    });
}
function generateHtml(data) {
  var html = '<td><select multiple class="form-control">';
  $.each(data,function(key,val) {
    html += '<option onclick="clickCategory(this)" value="'+val.CategoryID+'">'+val.CategoryName+'</option>'
  });
  html += '</select></td>';
  return   html;
}



function uploadStart(file) {
  var spu = $("input[name='spu']").val();
  if(spu==''){
    alert("未获取spu,请先填写spu");
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

//上传图片核心方法
function uploadPic(button_placeholder_id,cancelButtonId,progressTarget,upload_success_handler){
  var type    = "*.jpg;*.gif;*.png;*.JPG";
  var size    = "1 MB";
  var spu = $("input[name='spu']").val();
  var settings1 = {
    //flash_url : "http://source.huanhuan365.com/js/swfupload/swfupload/swfupload.swf",
    //upload_url: "http://source.huanhuan365.com/upload/"+companyId+"/"+imageName+randStr,
    flash_url : "/js/swfupload/swfupload/swfupload.swf",
    upload_url: "/ebayTemplate/uploadVariationPic/",
    post_params: {
      "spu":spu,
    },
    file_size_limit : size,
    file_types : type,
    file_types_description : type,
    file_upload_limit : 12,  //配置上传个数
    file_queue_limit : 12,
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