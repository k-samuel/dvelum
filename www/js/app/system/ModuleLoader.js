Ext.ns('app');
Ext.define('app.moduleLoader',{
    extend:'Ext.Base',
    mixins: {
        observable: 'Ext.util.Observable'
    },
    modules:{},
    loaded:[],
    loadModule:function(name, callback){
        if(Ext.isEmpty(this.modules[name])){
            Ext.Ajax.request({
                url: app.createUrl([app.admin,'index','moduleLoader']),
                method: 'post',
                scope:this,
                params:{
                    module:name
                },
                success: function(response, request) {
                    response =  Ext.JSON.decode(response.responseText);
                    var me = this;
                    if(response.success){
                        me.modules[name] = response.data;

                        if(!Ext.isEmpty(response.data.includes)){
                            me.loadScripts(response.data.includes,function(){
                                if(!Ext.isEmpty(callback)){
                                    callback();
                                }
                            });
                        }

                    }else{
                        Ext.Msg.alert(appLang.MESSAGE,response.msg);
                    }
                },
                failure:function() {
                    Ext.Msg.alert(appLang.MESSAGE, appLang.MSG_LOST_CONNECTION);
                }
            });
        }else{
            if(!Ext.isEmpty(callback)){
                callback();
            }
        }
    },
    loadScripts:function(list , callback){

        var scriptCount = 0;

        if(!Ext.isEmpty(list.js)){
            scriptCount+= list.js.length;
        }

        if(!Ext.isEmpty(list.css)){
            scriptCount+= list.css.length;
        }

        var me = this;

        Ext.each(list.css, function(item, index){
            if(Ext.Array.contains(me.loaded , item)){
                scriptCount --;
                if(scriptCount==0){
                    callback();
                }
                return;
            }
            Ext.Loader.loadScript({
                url:item,
                onLoad:function(){
                    scriptCount --;
                    me.loaded.push(item);
                    if(scriptCount==0){
                        callback();
                    }
                }
            });
        },me);

        Ext.each(list.js, function(item, index){
            if(Ext.Array.contains(me.loaded , item)){
                scriptCount --;
                if(scriptCount==0){
                    callback();
                }
                return;
            }
            Ext.Loader.loadScript({
                url:item,
                onLoad:function(){
                    scriptCount --;
                    me.loaded.push(item);
                    if(scriptCount==0){
                        callback();
                    }
                }
            });
        },me);
    }
});

