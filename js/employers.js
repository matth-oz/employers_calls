class Employers{

    empHandlerUrl = './ajax/employers_ajax.php';

    constructor(p){
        this.depLinkSelector = p.depLinkSelector;
        this.empWrapSelector = p.empWrapSelector;
        this.empSaveBtnSelector = p.empSaveBtnSelector;
        this.empResetBtnSelector = p.empResetBtnSelector;
        this.currenChecked = p.currenChecked;

        this.uncheckedEmployers = [];
    }


    // запускаем скрипты, после ответа ajax
    restartScripts (elemParent) {
        let scripts = Array.from(elemParent.getElementsByTagName('script'));
        for (let oldScript of scripts) {
            let newScript = document.createElement('script');
            newScript.text = oldScript.text;
            // Заменяем старый скрипт новым
            oldScript.parentNode.replaceChild(newScript, oldScript);
        }
    }

    async send(loadUrl, fdata){
        let response = await fetch(loadUrl, {
            method: 'POST',
            body: fdata,
        });       
        
        if (!response.ok) {
            throw new Error(`Ошибка по адресу ${loadUrl}, статус ошибки ${response.status}`);
        }       
      
        return await response.json();
    }

    operateEmployers(empCheckBxs, act, uncheckList = []){
        /*
        act - addRemEmp | clearEmps
        */
        
        let addStringToSend = ''; 
        let removeStringToSend = '';

        let fData = new FormData();
        fData.append('ajax', 'y');

        switch(act){
            case 'addRemEmp':
                if(empCheckBxs.length > 0){
                    empCheckBxs.forEach((input)=>{

                        addStringToSend += input.value + '|' + input.dataset.empname + '||';
                    });

                }

                if(uncheckList.length > 0){
                    uncheckList.forEach((inputVal)=>{
                        removeStringToSend += inputVal + '|';
                    });

                }


                if(addStringToSend !== ''){
                    fData.append('addEmpStr', addStringToSend);
                }
                
                if(removeStringToSend !== ''){
                    fData.append('remEmpStr', removeStringToSend);
                }
                
                fData.append('act', 'addRemEmp');

            break;
            case 'clearEmps':
                if(empCheckBxs.length > 0){
                    empCheckBxs.forEach((input)=>{
                        addStringToSend += input.value + '||';
                    });
                }

                fData.append('empStr', addStringToSend);
                fData.append('act', 'clearEmps');
            break;
        }

        this.send(this.empHandlerUrl, fData).then(data => {

            if(data.STATUS == 'success'){
                const empSaveBtn = document.querySelector(this.empSaveBtnSelector);
                const empResetBtn = document.querySelector(this.empResetBtnSelector);

                switch(act){
                    case 'addRemEmp':
                        this.currenChecked = data.RESULT;
                        
                        if(empCheckBxs.length == 0){                            
                            empSaveBtn.disabled = true;
                            empResetBtn.disabled = true;
                        }
                        
                        this.uncheckedEmployers = [];
                    break;
                    case 'clearEmps':
                        empCheckBxs.forEach((input)=>{
                            input.checked = false;
                        });

                        empSaveBtn.disabled = true;
                        empResetBtn.disabled = true;                           
                    break;
                }
            }
            else{
                data.MESS.forEach(mess => {
                    console.log('Ошибка: ' + mess);    
                });                    
            }
        })
        .catch((err) => {	            
            console.warn('Произошла ошибка', err);
        });        
    }

    init(){
        const depLinks = document.querySelectorAll(this.depLinkSelector);
        const empWrap = document.querySelector(this.empWrapSelector);
        const empWrapCheckboxes = empWrap.querySelectorAll('input[type="checkbox"]'); 
        const empSaveBtn = document.querySelector(this.empSaveBtnSelector);
        const empResetBtn = document.querySelector(this.empResetBtnSelector);

        depLinks.forEach((deplink)=>{
            deplink.addEventListener('click', (e) => {

                let depId = deplink.dataset.depid;

                let fData = new FormData();
                fData.append('ajax', 'y');
                fData.append('depId', depId);
                fData.append('act', 'listEmps');

                this.send(this.empHandlerUrl, fData).then(data => {
                    empWrap.innerHTML = '';
                    if(data.STATUS == 'success'){
                        
                        let output = ``;
                        let canReset = false;
                        Object.entries(data.RESULT).forEach(([key, row]) => {
                            let fullName = `${row.NAME}`;
                            if (typeof row.LAST_NAME !== 'undefined'){
                                fullName += ` ${row.LAST_NAME}`;
                            } 

                            if(!this.currenChecked.includes(row.ID) && row.CHECKED === 'Y'){
                                this.currenChecked.push(row.ID);
                            }
                            
                            let checked = (row.CHECKED === 'Y') ? ' checked' : '';

                            if(row.CHECKED === 'Y' && !canReset){ 
                                canReset = true;
                            }

                            output += `<label>`;
                            output += `<input class="form-check-input" ${checked} type="checkbox" name="employers[]" value="${row.ID}" data-empname="${fullName}" />`;
                            output += fullName;
                            output += `</label>`;
                        });

                        empWrap.insertAdjacentHTML('afterbegin', output);

                        if(canReset){
                            empResetBtn.disabled = false;
                        }


                    }
                    else{
                        data.MESS.forEach(mess => {
                            console.log('Ошибка: ' + mess);  
                        });                    
                    }

                    // this.restartScripts(document.body);

                    empWrapCheckboxes.forEach(empCheckBx => {
                        empCheckBx.onclick = function(e){
                            console.log(empCheckBx.value);
                        }
                    })

                }).catch((err) => {	            
                    console.warn('Произошла ошибка', err);
                });

                e.preventDefault();
            });
        });

        
        // Работа с чекбоксами: сохраняем удаляемых из массива сотрудников 
        empWrap.addEventListener('click', (e)=>{
            let checkedElems = empWrap.querySelectorAll('input:checked');

            let empCheckBx = e.target;
            if(empCheckBx.checked === false){
                // чекбокс снимается…

                empCheckBx.value = parseInt(empCheckBx.value);

                if(this.currenChecked.includes(empCheckBx.value) && !this.uncheckedEmployers.includes(empCheckBx.value)){
                    this.uncheckedEmployers.push(empCheckBx.value);
                }

                if(checkedElems.length > 0){
                    empResetBtn.disabled = false;
                }
                else{
                    empResetBtn.disabled = true;
                }

                if(checkedElems.length > 0 || (checkedElems.length == 0 && this.uncheckedEmployers.length !== 0)){
                    empSaveBtn.disabled = false;                        
                }
                else{
                    empSaveBtn.disabled = true;                        
                }                    
            }
            else{
                // чекбокс отмечается…
                if(empSaveBtn.disabled === true) empSaveBtn.disabled = false;

                //let checkedElems = empWrap.querySelectorAll('input:checked'); 
                if(checkedElems.length > 0){
                    empSaveBtn.disabled = false;
                    empResetBtn.disabled = false;
                }
                else{
                    empSaveBtn.disabled = true;
                    empResetBtn.disabled = true;
                }

                let key = this.uncheckedEmployers.indexOf(empCheckBx.value);
                if(key !== -1 && this.currenChecked.includes(empCheckBx.value)){
                    this.uncheckedEmployers.splice(key, 1);
                }
            }
        });

        // Очищаем чекбоксы и удаляем из массива сотрудников
        empResetBtn.addEventListener('click', (e)=>{

            let inputs = empWrap.querySelectorAll('input:checked');
            this.operateEmployers(inputs, 'clearEmps');

            e.preventDefault();
        });

        // добавляем / удаляем 
        empSaveBtn.addEventListener('click', (e)=>{

            // добавляемые или существующие сотрудники отдела
            let inputs = empWrap.querySelectorAll('input:checked');

            // передаем добаляемых [inputs] и удаляемых [this.uncheckedEmployers] сотрудников           
            this.operateEmployers(inputs, 'addRemEmp', this.uncheckedEmployers);

            e.preventDefault();
        });

    }
}