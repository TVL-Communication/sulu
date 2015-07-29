define(function(){"use strict";var a={data:{},instanceName:"collection"},b={settingsFormId:"collection-settings"};return{view:!0,layout:{navigation:{collapsed:!0},content:{width:"fixed"}},templates:["/admin/media/template/collection/settings"],initialize:function(){this.options=this.sandbox.util.extend(!0,{},a,this.options),this.saved=!0;var b="/admin/api/collections/"+this.options.data.id+"?depth=1&sortBy=title";this.sandbox.emit("husky.navigation.select-id","collections-edit",{dataNavigation:{url:b}}),this.bindCustomEvents(),this.render(),this.sandbox.sulu.triggerDeleteSuccessLabel("labels.success.collection-deleted-desc")},bindCustomEvents:function(){this.sandbox.on("sulu.header.back",function(){this.sandbox.emit("sulu.media.collections.list")}.bind(this)),this.sandbox.on("sulu.header.language-changed",this.changeLanguage.bind(this)),this.sandbox.on("sulu.toolbar.save",this.save.bind(this))},changeLanguage:function(a){this.sandbox.emit("sulu.media.collections.reload-collection",this.options.data.id,{locale:a.id,breadcrumb:"true"},function(a){this.options.data=a,this.sandbox.form.setData("#"+b.settingsFormId,this.options.data)}.bind(this)),this.sandbox.emit("sulu.media.collections-edit.set-locale",a.id)},render:function(){this.sandbox.dom.html(this.$el,this.renderTemplate("/admin/media/template/collection/settings")),this.sandbox.start("#"+b.settingsFormId),this.sandbox.form.create("#"+b.settingsFormId),this.sandbox.form.setData("#"+b.settingsFormId,this.options.data).then(function(){this.bindDomEvents()}.bind(this))},bindDomEvents:function(){this.sandbox.dom.on("#"+b.settingsFormId,"change keyup",function(){this.saved===!0&&(this.sandbox.emit("sulu.header.toolbar.item.enable","save-button",!1),this.saved=!1)}.bind(this))},save:function(){if(this.sandbox.form.validate("#"+b.settingsFormId)){var a=this.sandbox.form.getData("#"+b.settingsFormId);this.options.data=this.sandbox.util.extend(!0,{},this.options.data,a),this.options.data.parent=this.options.data._embedded.parent?this.options.data._embedded.parent.id:null,this.sandbox.emit("sulu.header.toolbar.item.loading","save-button"),this.sandbox.once("sulu.media.collections.collection-changed",this.savedCallback.bind(this)),this.sandbox.emit("sulu.media.collections.save-collection",this.options.data)}},savedCallback:function(){this.sandbox.emit("sulu.header.toolbar.item.disable","save-button",!0),this.saved=!0,this.sandbox.emit("sulu.labels.success.show","labels.success.collection-save-desc","labels.success"),this.sandbox.emit("husky.data-navigation.collections.reload")}}});