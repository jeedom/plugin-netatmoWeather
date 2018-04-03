<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('netatmoWeather');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
  <div class="col-sm-2">
    <div class="bs-sidebar">
      <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
        <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
        <?php
foreach ($eqLogics as $eqLogic) {
	$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
	echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '" style="' . $opacity . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
}
?>
     </ul>
   </div>
 </div>
 	<div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
   <legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
   <div class="eqLogicThumbnailContainer">
  <div class="cursor eqLogicAction" data-action="gotoPluginConf" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;">
    <center>
      <i class="fa fa-wrench" style="font-size : 5em;color:#767676;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Configuration}}</center></span>
  </div>
  <div class="cursor" id="bt_healthNetatmoWeather" style="background-color : #ffffff; height : 120px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
    <center>
      <i class="fa fa-medkit" style="font-size : 5em;color:#767676;"></i>
    </center>
    <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#767676"><center>{{Santé}}</center></span>
  </div>
</div>
  <legend><i class="icon nature-weather1"></i>  {{Mes Stations}}
  </legend>
  <?php
if (count($eqLogics) == 0) {
	echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n'avez pas encore de station Netatmo, aller sur Général -> Plugin et cliquez sur synchroniser pour commencer}}</span></center>";
} else {
	?>
   <div class="eqLogicThumbnailContainer">
    <?php
foreach ($eqLogics as $eqLogic) {
		$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
		echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
		echo "<center>";
		if ($eqLogic->getConfiguration('type', '') != '') {
			echo '<img src="plugins/netatmoWeather/core/img/' . $eqLogic->getConfiguration('type', '') . '.png" height="105" width="95" />';
		} else {
			echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
		}
		echo "</center>";
		echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
		echo '</div>';
	}
	?>
 </div>
 <?php }
?>
</div>
<div class="col-sm-10 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
 <div class="row">
  <div class="col-sm-6">
    <form class="form-horizontal">
     <fieldset>
      <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i> {{Général}}<i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i></legend>
      <div class="form-group">
        <label class="col-sm-4 control-label">{{Nom de l'équipement météo Netatmo}}</label>
        <div class="col-sm-6">
          <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
          <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement météo Netatmo}}"/>
        </div>
      </div>
      <div class="form-group">
        <label class="col-sm-4 control-label" >{{Objet parent}}</label>
        <div class="col-sm-6">
          <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
            <option value="">{{Aucun}}</option>
            <?php
foreach (object::all() as $object) {
	echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
}
?>
         </select>
       </div>
     </div>
     <div class="form-group">
       <label class="col-sm-4 control-label"></label>
       <div class="col-sm-8">
	<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
       </div>
     </div>
     <div class="form-group">
      <label class="col-sm-4 control-label">{{Identifiant}}</label>
      <div class="col-sm-6">
        <span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="logicalId"></span>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label">{{Type}}</label>
      <div class="col-sm-6">
        <select type="text" disabled class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type" >
          <option value="station">{{Station}}</option>
          <option value="module_ext">{{Module extérieur}}</option>
          <option value="module_int">{{Module intérieur}}</option>
          <option value="module_rain">{{Module pluie}}</option>
          <option value="module_wind">{{Anémomètre}}</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label">{{Firmware}}</label>
      <div class="col-sm-6">
        <span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="configuration" data-l2key="firmware"></span>
      </div>
    </div>
    <div class="form-group">
      <label class="col-sm-4 control-label">{{Réception réseaux}}</label>
      <div class="col-sm-6">
        <span class="label label-info" style="font-size:1em;">
          <span class="eqLogicAttr" data-l1key="configuration" data-l2key="wifi_status"></span>
          <span class="eqLogicAttr" data-l1key="configuration" data-l2key="rf_status"></span>
        </span>
      </div>
    </div>
    <div class="form-group" id="battery_net_weather">
      <label class="col-sm-4 control-label">{{Batterie}}</label>
      <div class="col-sm-6">
        <span class="label label-info" style="font-size:1em;">
          <span class="eqLogicAttr" data-l1key="configuration" data-l2key="batteryStatus"></span> %
        </span>
      </div>
    </div>
  </fieldset>
</form>
</div>
<div class="col-sm-6">
  <center>
    <img src="' . $plugin->getPathImgIcon() . '" id="img_netatmoModel" style="height : 300px;margin-top : 60px" />
  </center>
</div>
</div>

<legend><i class="fa fa-list-alt"></i>  {{Météo Netatmo}}</legend>
<table id="table_cmd" class="table table-bordered table-condensed">
  <thead>
    <tr>
      <th>{{Nom}}</th><th>{{Option}}</th><th>{{Action}}</th>
    </tr>
  </thead>
  <tbody>
  </tbody>
</table>

<form class="form-horizontal">
  <fieldset>
    <div class="form-actions">
      <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
      <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
    </div>
  </fieldset>
</form>

</div>
</div>

<?php include_file('desktop', 'netatmoWeather', 'js', 'netatmoWeather');?>
<?php include_file('core', 'plugin.template', 'js');?>
