
function CallsTableHandler(p){
    var o = this;
    
    this.jCallsFilter = p.jCallsFilter;
    this.fPortalUserId = p.fPortalUserId,
    this.fCallsDateFrom = p.fCallsDateFrom,
    this.fCallsDateTo = p.fCallsDateTo,
    this.jProcessBlock = p.jProcessBlock;
    this.jBfTbl = p.jBfTbl;
    this.jExportXls = p.jExportXls;
    this.jCallsRowsRoot = p.jCallsRowsRoot;
    this.jCallsTable = p.jCallsTable;

    this.ajaxHandlerUrl = '';


    this.send = async function(loadUrl, fdata){
        let response = await fetch(loadUrl, {
            method: 'POST',
            body: fdata,
        });       
        
        if (!response.ok) {
            throw new Error(`Ошибка по адресу ${loadUrl}, статус ошибки ${response.status}`);
        }       
      
        return await response.json();
    }

    this.validateForm = function(pid, cdf, cdt){

        let isValidForm = false;

        if(pid.value == '---'){
            pid.nextElementSibling.classList.remove('vh');
        }

        if(cdf.value == ''){
            cdf.nextElementSibling.classList.remove('vh');
        }
        
        if(cdt.value == ''){
            cdt.nextElementSibling.classList.remove('vh');
        }

        if(pid.value !== '---' && cdf.value !== '' && cdt.value !== ''){
            isValidForm = true;
        }

        return isValidForm;
    }

    this.init = function(){
        const jCallsFilter = document.querySelector(o.jCallsFilter);

        o.ajaxHandlerUrl = jCallsFilter.getAttribute('action');
        
        const jProcessBlock = document.querySelector(o.jProcessBlock);
        const jBfTbl = document.querySelector(o.jBfTbl);
        const jExportXls = document.querySelector(o.jExportXls);
        const jCallsRowsRoot = document.querySelector(o.jCallsRowsRoot);

        const fPortalUserId = document.getElementById(o.fPortalUserId);
        const fCallsDateFrom = document.getElementById(o.fCallsDateFrom);
        const fCallsDateTo = document.getElementById(o.fCallsDateTo);

        const jCallsTable = document.querySelector(o.jCallsTable);

        fPortalUserId.addEventListener('focus', (e) => {
            fPortalUserId.nextElementSibling.classList.add('vh');
        });
        
        fCallsDateFrom.addEventListener('focus', (e) => {
            fCallsDateFrom.nextElementSibling.classList.add('vh');
        });

        fCallsDateTo.addEventListener('focus', (e) => {
            fCallsDateTo.nextElementSibling.classList.add('vh');
        });

        jCallsFilter.addEventListener('submit', (e) => {
            
           let isValid =  o.validateForm(fPortalUserId, fCallsDateFrom, fCallsDateTo);

           // console.log(isValid);

           if(isValid){          

                let formData = new FormData(jCallsFilter);
                formData.append('ajax', 'y');

                jProcessBlock.classList.remove('dn');

                o.send(o.ajaxHandlerUrl, formData).then(data => {
                    if(data.STATUS == 'success'){
                        let output = ``;

                        //Object.entries(data.RESULT).forEach(([key, value]) => { console.log(key, value) });

                        Object.entries(data.RESULT).forEach(([key, row]) => {
                            output += `<tr>`;
                            output += `<td>${row.PORTAL_USER}</td>`;
                            output += `<td>${row.PORTAL_NUMBER}</td>`;
                            output += `<td>${row.PHONE_NUMBER}</td>`;
                            output += `<td>${row.CALL_TYPE}</td>`;
                            output += `<td>${row.CALL_DURATION}</td>`;
                            output += `<td>${row.CALL_START_DATE}</td>`;
                            output += `<td>${row.CALL_STATUS}</td>`;
                            output += `<td>${row.CRM_ENTITY}</td>`;
                            output += `<td>${row.CRM_ENTITY_COMPANY}</td>`;
                            output += `</tr>`; 
                        });

                        jCallsRowsRoot.insertAdjacentHTML('afterbegin', output);
                        jCallsTable.classList.remove('dn');
                        jProcessBlock.classList.add('dn');
                        jProcessBlock.children[0].classList.remove('visually-hidden');
                        jBfTbl.classList.remove('vh');
                        let href = './calls_export.php?ds=' + data.SOURCE;
                        jExportXls.setAttribute('href', href);
                    }
                    else{
                        data.MESS.forEach(mess => {
                            console.log('Ошибка: ' + mess);    
                        });                    
                    }

                    console.dir(data);

                }).catch((err) => {	            
                    console.warn('Произошла ошибка', err);
                });
            }
            e.preventDefault();
        });
    }
}



