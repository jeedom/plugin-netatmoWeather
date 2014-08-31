
/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

$(function() {
    $(".li_eqLogic").on('click', function(event) {
        printNetatmoWeather($(this).attr('data-eqLogic_id'));
        return false;
    });
    $("#searchDevices").on('click', function(event) {
        searchNetatmoDevices($(this).attr('data-eqLogic_id'),$('#client_id').val(),$('#client_secret').val(),$('#username_netatmo').val(),$('#password_netatmo').val());
        return false;
    });
    $("#createDevices").on('click', function(event) {
        createNetatmoDevices($(this).attr('data-eqLogic_id'),$('#client_id').val(),$('#client_secret').val(),$('#username_netatmo').val(),$('#password_netatmo').val());
        return false;
    });
    $("#sel_station").on('change', function() {
        var optionSelected = $(this).find("option:selected").text();
        if(optionSelected!=''){
        	var type=optionSelected.split(':');	
        	$("#type").val(type[0]);
        }
        
        
        $("#station_id").val(this.value);       
        return false;
    });
});

function addCmdToTable() {
}

function printNetatmoWeather(_netatmoWeatherEq_id) {
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/netatmoWeather/core/ajax/netatmoWeather.ajax.php", // url du fichier php
        data: {
            action: "getWeather",
            id: _netatmoWeatherEq_id
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
        	if (data.state != 'ok') {
                $('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
            $('#table_netatmoWeather tbody').empty();
            $('#div_netatmoWeather').empty();
            $('#div_netatmoWeather').append(data.result.print);
            for (var i in data.result.cmd) {
            	if (data.result.cmd[i].value != null) {
                	var tr = '<tr>';
                	tr += '<td>' + data.result.cmd[i].name + '</td>';
                	tr += '<td>' + data.result.cmd[i].value;
                	if (data.result.cmd[i].unite != null) {
                    	tr += ' ' + data.result.cmd[i].unite;
                	}
                	tr += '</td>';
                	tr += '</tr>';
                	$('#table_netatmoWeather tbody').append(tr);
               }
            }
        }
    });
}

function searchNetatmoDevices(_netatmoWeatherEq_id,client_id,client_secret,username,password) {
	$.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/netatmoWeather/core/ajax/netatmoWeather.ajax.php", // url du fichier php
        data: {
            action: "getDevicesList",
            id: _netatmoWeatherEq_id,
            client_id: client_id,
            client_secret: client_secret,
            username: username,
            password: password
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
           
            for (var i in data.result.cmd.devices) {
            	$('#sel_station').prop('disabled', false);
            	$('#sel_station').empty();
            	$('#sel_station').append(new Option('Station:'+data.result.cmd.devices[i].module_name,data.result.cmd.devices[i]._id));
            	if($('#station_id').val() ==""){
            		$('#station_id').val(data.result.cmd.devices[i]._id);
            		$('#type').val('Station');
            	} 
            	var type='';          	
                for (var j in data.result.cmd.devices[i].modules) {
                	
                	if(data.result.cmd.devices[i].modules[j].type=="NAModule1"){
                		type='module_ext';
                	}else if(data.result.cmd.devices[i].modules[j].type=="NAModule4"){
                		type='module_int';
                	}else if(data.result.cmd.devices[i].modules[j].type=="NAModule3"){
                		type='module_rain';
                	}
                	$('#sel_station').append(new Option(type+':'+data.result.cmd.devices[i].modules[j].module_name,data.result.cmd.devices[i].modules[j]._id));
                }
            }
           
        }
    });
}

function createNetatmoDevices(_netatmoWeatherEq_id,client_id,client_secret,username,password) {
	$.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // methode de transmission des données au fichier php
        url: "plugins/netatmoWeather/core/ajax/netatmoWeather.ajax.php", // url du fichier php
        data: {
            action: "saveDevicesList",
            id: _netatmoWeatherEq_id,
            client_id: client_id,
            client_secret: client_secret,
            username: username,
            password: password
        },
        dataType: 'json',
        error: function(request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function(data) { // si l'appel a bien fonctionné
            
            if (data.state != 'ok') {
            	$('#div_alert').showAlert({message:  data.result,level: 'danger'});
                return;
            }
            modifyWithoutSave=false;
             window.location.reload();
        }
    });
}
