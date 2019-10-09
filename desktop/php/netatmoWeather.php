<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('netatmoWeather');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fa fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			<div class="cursor eqLogicAction logoPrimary" data-action="gotoPluginConf">
				<i class="fa fa-wrench"></i>
				<br>
				<span>{{Configuration}}</span>
			</div>
			<div class="cursor logoSecondary" id="bt_healthNetatmoWeather">
				<i class="fa fa-medkit"></i>
				<br>
				<span>{{Santé}}</span>
			</div>
		</div>
		<legend><i class="icon nature-weather1"></i> {{Mes Stations}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<?php
		if (count($eqLogics) == 0) {
			echo "<br/><br/><br/><center><span style='color:#767676;font-size:1.2em;font-weight: bold;'>{{Vous n’avez pas encore de station Netatmo, cliquez sur configuration et cliquez sur synchroniser pour commencer}}</span></center>";
		} else {
			?>
			<div class="eqLogicThumbnailContainer">
				<?php
				foreach ($eqLogics as $eqLogic) {
					$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
					echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
					if ($eqLogic->getConfiguration('type', '') != '') {
						echo '<img src="plugins/netatmoWeather/core/img/' . $eqLogic->getConfiguration('type', '') . '.png" />';
					} else {
						echo '<img src="' . $plugin->getPathImgIcon() . '" />';
					}
					echo '<br/>';
					echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
					echo '</div>';
				}
				?>
			</div>
		<?php }
		?>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
				<div class="row">
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
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
											foreach (jeeObject::all() as $object) {
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
									<label class="col-sm-4 control-label">{{Type}}</label>
									<div class="col-sm-6">
										<select disabled class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="type">
											<option value="NAMain">{{Station}}</option>
											<option value="NAModule1">{{Module extérieur}}</option>
											<option value="NAModule4">{{Module intérieur}}</option>
											<option value="NAModule3">{{Module pluie}}</option>
											<option value="NAModule2">{{Anémomètre}}</option>
										</select>
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
								<div class="form-group">
									<label class="col-sm-4 control-label">{{Identifiant}}</label>
									<div class="col-sm-6">
										<span class="eqLogicAttr label label-info" style="font-size:1em;" data-l1key="logicalId"></span>
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
							</fieldset>
						</form>
						<center>
							<img src="<?php echo $plugin->getPathImgIcon(); ?>" id="img_netatmoModel" style="height : 250px;margin-top : 60px" />
						</center>
					</div>
				</div>
			</div>
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<legend><i class="fa fa-list-alt"></i>  {{Météo Netatmo}}</legend>
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Nom}}</th><th style="width: 300px;">{{Option}}</th><th>{{Action}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'netatmoWeather', 'js', 'netatmoWeather');?>
<?php include_file('core', 'plugin.template', 'js');?>
