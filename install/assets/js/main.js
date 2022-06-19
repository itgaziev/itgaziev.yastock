var ITGazievCondition = function(args) {
    this.default = {
        iblock : 0,
        filter: {
            condition : [],
            rule : [],
        },
        data : null,
        ajax : {
            url : '/bitrix/admin/itgaziev.yastock_ajax.php',
            options : {
                templateResult : this.templateResultFunc,
                templateSelection : this.templateSelectionFunc,
            }
        }
    };

    this.options = Object.assign(this.default, args);
    console.log(this.options)

    this.init = () => {
        $('.condition-attribute-select').select2({data : this.options.data.select, width : 'style', placeholder : 'Выберите значение'});
    }

    this.foundParams = (id) => {
        let result = this.options.data.data.filter((item) => item.id === id);
        return result[0]
    }

    this.compareType = (id, group, index, replace = true) => {
        let res = this.foundParams(id);
        let founds = this.options.compare.filter(item => item.id === res.compare)[0];
        if(replace) {
            let template = `
                <select name="CONDITION[${group}][${index}][compare]" class="condition-select condition-compare-select"></select>
            `;
            $('.box[data-group="' + group + '"] .condition-rule-item[data-index="' + index + '"] .condition-rule-item__compare').html(template);
        }
        $('select[name="CONDITION['+group+']['+index+'][compare]"]').select2({data : founds.list, width : 'style', placeholder : 'Выберите значение'});
    }

    this.templateCompare = (id, group, index) => {
        let res = this.foundParams(id);
        let template = '';
        let events = null;
        console.log(res)
        switch(res.compare) {
            case 'section':
            case 'element':
            case 'list':
            case 'hload':
                template = this.ajaxSelectTemplate(group, index);
                events = this.eventAjaxSelect;
                break;
            case 'bool':
                template = this.boolSelect(group, index);
                events = this.eventsBool;
                break;
            case 'store':
                template = this.storeInput(group, index);
                break;
            default: template = this.textTemplate(group, index);

        }

        $('.box[data-group="' + group + '"] .condition-rule-item[data-index="' + index + '"] .condition-rule-item__values').html(template);
        if(events) {
            events(group, index, {
                iblock : this.options.iblock,
                select : res
            });
        }
    }
    this.templateCompareDefault = (id, group, index) => {
        let res = this.foundParams(id);
        let template = '';
        let events = null;
        console.log(res)
        switch(res.compare) {
            case 'section':
            case 'element':
            case 'list':
            case 'hload':
                this.eventAjaxSelect(group, index, {
                    iblock : this.options.iblock,
                    select : res
                })
                break;
            case 'bool':
                this.eventsBool(group, index, {});
                break;
        }
    }
    this.storeInput = (group, index) => {
        return `
            <input type="number" name="CONDITION[${group}][${index}][values]" value="По строке" placeholder="" class="condition-input"/>
        `
    }
    this.boolSelect = (group, index) => {
        return `
            <select name="CONDITION[${group}][${index}][values]" class="condition-select condition-bool-select">
                <option value="N">Нет</option>
                <option value="Y">Да</option>
            </select>
        `;
    }
    this.ajaxSelectTemplate = (group, index) => {
        return `
            <select name="CONDITION[${group}][${index}][values][]" class="condition-select condition-ajax-select" multiple></select>
        `;
    }

    this.textTemplate = (group, index) => {
        return `
            <input type="text" name="CONDITION[${group}][${index}][values]" value="По строке" placeholder="" class="condition-input"/>
        `
    }
    this.eventsBool = (group, index, options) => {
        
        $('select[name="CONDITION['+group+']['+index+'][values]').select2();
    }
    this.eventAjaxSelect = (group, index, options) => {
        $('select[name="CONDITION['+group+']['+index+'][values][]"]').select2({
            ajax: {
                transport : (params, success, failure) => {
                    let page = params.page === undefined ? 1 : params.page
                    var $request = BX.ajax({
                        url : '/bitrix/admin/itgaziev.yastock_ajax.php',
                        data : {
                            action : 'search',
                            params : params,
                            options : options
                        },
                        method: 'POST',
                        dataType: 'json',
                        timeout: 3600,
                        async: true,
                        processData : true,
                        scriptsRunFirst : true,
                        emulateOnload : true,
                        start: true,
                        onsuccess : success,
                        onfailure: () => {}
                    });
                    return $request;
                },
                processResults : (data, params) => {
                    console.log(data)
                    params.page = params.page || 1;
                    if(data !== undefined && data !== null ) {
                        return { 
                            results: data.results,
                            pagination: {
                                more: (params.page * 30) < data.total_count
                            }
                        };
                    } else {
                        return { results: [] };
                    }
                },
                cache : false
            },
            placeholder: 'Поиск ...',
            minimumInputLength: 1,
            templateResult : (repo) => {
                let container = `
                <div class='select2-result-repository clearfix'>
                    <div class='select2-result-repository__meta'>
                        <div class='select2-result-repository__title'>${repo.name}</div>
                    </div>
                </div>`; 
    
                return $(container);  
            },
            templateSelection: (repo) => {
                return repo.name
            }
        })
    }

    this.templateResultFunc = (repo) => {
        let container = `
        <div class='select2-result-repository clearfix'>
            <div class='select2-result-repository__meta'>
                <div class='select2-result-repository__title'>${repo.name}</div>
            </div>
        </div>`; 

        return $(container);
    },

    this.getLastIndexRule = (group_id) => {
        var lastIndex = 0;        
        $('.box[data-group="' + group_id + '"] .condition-rule-item').each( function(){
            let index = $(this).attr('data-index')
            lastIndex = parseInt(index) + 1;
        })

        return lastIndex;
    }

    this.addRule = (group_id, index) => {
        let template = `
        <div class="condition-rule-item" data-index="${index}">
            <div class="colums condition-rule-item__attribute">
                <select name="CONDITION[${group_id}][${index}][attribute]" class="condition-select condition-attribute-select"></select>
            </div>
            <div class="colums condition-rule-item__compare">
                <select name="CONDITION[${group_id}][${index}][compare]" class="condition-select condition-compare-select"></select>
            </div>
            <div class="colums condition-rule-item__values">
            </div>
            <div class="colums condition-rule-item__remove">
                <span class="remove-rule">Удалить условие</span>
            </div>
        </div>
        `;

        $('.box[data-group="' + group_id + '"] .condition-add').append(template);

        $('.condition-select[name="CONDITION['+group_id+']['+index+'][attribute]"]').select2({data : this.options.data.select, width : 'style', placeholder : 'Выберите значение'});
        this.templateCompare($('.condition-select[name="CONDITION['+group_id+']['+index+'][attribute]"]').val(), group_id, index);
        this.compareType($('.condition-select[name="CONDITION['+group_id+']['+index+'][attribute]"]').val(), group_id, index);
    }

    this.addBox = () => {
        var lastIndex = 0;
        $('.box').each( function(){
            let index = $(this).attr('data-group')
            lastIndex = parseInt(index) + 1;
        })

        let template = `
            <div class="box" data-group="${lastIndex}">
                <div class="condition-add">

                </div>
                <div class="footer-box">
                    <span class="add-rule">Добавить условие</span>
                    <span class="remove-rule-group">Удалить группу</span>
                </div>
            </div>

        `

        $('.box-add').append(template)
    }

    this.removeBox = (group_id) => {
        $('.box[data-group="' + group_id + '"]').remove();
    }
}

var ITGazievExport = function(args) {
    this.default = {
        iblock : 0,
        data : null,
    };

    this.options = Object.assign(this.default, args);

    this.ajaxExport = (options) => {
        var params = options;
        console.log(options, 'newoption')
        var _this = this;
        BX.ajax({
            url : '/bitrix/admin/itgaziev.yastock_ajax.php',
            data : {
                action : 'export',
                params : params,
                options : {}
            },
            method: 'POST',
            dataType: 'json',
            timeout: 3600,
            async: true,
            processData : true,
            scriptsRunFirst : true,
            emulateOnload : true,
            start: true,
            onsuccess : (response) => {
                if(response.action == 'saved') {
                    console.log('end import');
                } else {
                    let newoptions = {...options, ...response}
                    setTimeout(() => {
                        _this.ajaxExport(newoptions)
                    }, 5000)
                    $('.myBar').css('width', response.per + '%');
                    console.log(newoptions)
                    console.log(response)
                }
            },
            onfailure: () => {}
        });
    }
}