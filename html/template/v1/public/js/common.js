$(function () {
    window.Modal = function () {
    var reg = new RegExp("\\[([^\\[\\]]*?)\\]", 'igm');
    var alr = $("#ycf-alert");
    var ahtml = alr.html();
    var _alert = function (options) {
      alr.html(ahtml);  // 复原
      alr.find('.ok').removeClass('btn-success').addClass('btn-primary');
      alr.find('.cancel').hide();
      _dialog(options);
      return {
        on: function (callback) {
          if (callback && callback instanceof Function) {
            alr.find('.ok').click(function () { callback(true) });
          }
        }
      };
    };
    var _confirm = function (options) {
      alr.html(ahtml); // 复原
      alr.find('.ok').removeClass('btn-primary').addClass('btn-success');
      alr.find('.cancel').show();
      _dialog(options);
      return {
        on: function (callback) {
          if (callback && callback instanceof Function) {
            alr.find('.ok').click(function () { callback(true) });
            alr.find('.cancel').click(function () { callback(false) });
          }
        }
      };
    };
    var _dialog = function (options) {
      var ops = {
        msg: "提示内容",
        title: "操作提示",
        btnok: "确定",
        btncl: "取消"
      };

      $.extend(ops, options);

      console.log(alr);

      var html = alr.html().replace(reg, function (node, key) {
        return {
          Title: ops.title,
          Message: ops.msg,
          BtnOk: ops.btnok,
          BtnCancel: ops.btncl
        }[key];
      });
      
      alr.html(html);
      alr.modal({
        width: 500,
        backdrop: 'static'
      });
    }
    return {
      alert: _alert,
      confirm: _confirm
    }
  }();
});
function alert_success(msg){
  var options  = {
    msg: msg,
    title: '成功',
    btnok: '确定'
  };
  $(".modal").modal("hide");
  Modal.alert(options);
}
function alert_failed(msg){
  var options  = {
    msg: msg,
    title: '失败',
    btnok: '确定'
  };
  var old_id = '';
  $(".modal").each(function(){
    if($(this).attr("aria-hidden")=='false'){
      old_id = $(this).attr("id");
      $(this).modal("hide");
    }
  });
  Modal.alert(options);
  // if(old_id!=''){
  //   $("#"+old_id).modal("show");
  // }
}
function alert_confirm(msg,func){
  $(".modal").modal("hide");
  Modal.confirm({msg: msg}).on(func);
}