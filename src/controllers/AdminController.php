<?php namespace crocodicstudio\crudbooster\controllers;

use crocodicstudio\crudbooster\controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\PDF;
use Illuminate\Support\Facades\Excel;

class AdminController extends CBController {	
	
	var $appname;

	function __construct() {	
		$this->init_setting();
		$data = array();	
		if($this->setting) $data['appname'] = $this->setting->appname;
		view()->share($data);
	}

	function getIndex() {

		$data = array();
			
		$id_cms_privileges = Session::get('admin_privileges');
		$id_cms_privileges = (Session::get('dashboard_config_id_privileges'))?:$id_cms_privileges;
		$id_cms_privileges = intval($id_cms_privileges);
		$data['list_id_dashboard'] = DB::table('cms_dashboard')->where("id_cms_privileges",$id_cms_privileges)->orderby("id","asc")->lists('id');

		$data['page_title']       = '<strong>Dashboard</strong> ';
		$data['page_description'] = get_setting('appname');
		$data['page_menu']        = Route::getCurrentRoute()->getActionName();
		$data['setting']          = $this->setting;
		return view('crudbooster::home',$data);
	}

	public function getSetDashboardConfigMode() {

		if(!get_is_superadmin()) {
			return redirect('admin')->with(['message'=>'Sorry The Configuration Dashboard only Available for Super Admin','message_type'=>'warning']);
		}

		Session::put('dashboard_config_mode',1);
		Session::put('dashboard_config_id_privileges',intval(Request::get('id_cms_privileges')));
		return redirect('admin');
	}
	public function getUnsetDashboardConfigMode() {
		Session::forget('dashboard_config_mode');
		Session::forget('dashboard_config_id_privileges');
		return redirect('admin');	
	}

	public function getSettingStatNumber($id='') {
		$row = '';
		@$row = DB::table('cms_dashboard')->where('id',$id)->first();
		@$content = unserialize($row->content);

		$html = "<form method='post' action='".route('AdminControllerPostSaveCmsDashboard')."'>";
		$html .= "<input type='hidden' name='type' value='statistic_number'/>";
		$html .= "<input type='hidden' name='id' value='$id'/>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>LABEL NAME</label><input required type='text' name='label' value='$content[label]' class='form-control'/>";
		$html .= "</div>";

		$icons = array('ion-connection-bars','ion-pie-graph','ion-stats-bars','ion-arrow-graph-up-left','ion-person-stalker','ion-email','ion-archive','ion-location','ion-model-s','ion-cube','ion-ios-people');
		$icons_select = "";
		foreach($icons as $icon) {
			$checked = ($icon == $content['icon'])?"checked":"";
			$icons_select .= "<input type='radio' $checked name='icon' value='$icon'> <i class='ion $icon'></i> &nbsp;&nbsp;";
		}		

		$html .= "<div class='form-group'>";
			@$html .= "<label>ICON</label><br/>$icons_select";
		$html .= "</div>";

		$color_list = array('red','green','aqua','yellow');
		$color_select = "<select required name='color' class='form-control'>";
		foreach($color_list as $color) {
			@$selected = ($color==$content['color'])?"selected":"";
			$color_select .= "<option value='$color' $selected >$color</option>";
		}
		$color_select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>COLOR</label>$color_select";
		$html .= "</div>";

		$list_table = "<select required class='form-control' name='table_name'><option value=''>** Select a Table</option>";

		$tables = list_tables();		
		foreach($tables as $tab) {
			foreach ($tab as $key => $value) {				
				@$selected = ($value == $content['table_name'])?"selected":"";
				$list_table .= "<option $selected value='$value'>$value</option>";
			}
		}

		$list_table .= "</select>";


		$html .= "<div class='form-group'>";
			$html .= "<label>TABLE</label>$list_table";
		$html .= "</div>";

		$aggregate_list = array("count","sum","avg","min","max");
		$aggregate_select = "<select required name='aggregate_type' class='form-control'>";
		foreach($aggregate_list as $list) {
			@$selected = ($list == $content['aggregate_type'])?"selected":"";
			$aggregate_select .= "<option $selected value='$list'>$list</option>";
		}
		$aggregate_select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>AGGREGATE TYPE</label>$aggregate_select";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>COLUMN</label><select required name='column' data-current='$content[column]' class='form-control'></select>";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL WHERE QUERY</label><textarea required name='sql_where' class='form-control'>$content[sql_where]</textarea>";
			@$html .= "<div class='help-block'>You can use alias [admin_id_companies],[admin_id]</div>";
		$html .= "</div>";

		$html .= "</form>";

		echo $html;
	}

	public function getSettingChartLine($id='') {
		$row = '';
		@$row = DB::table('cms_dashboard')->where('id',$id)->first();
		@$content = unserialize($row->content);

		$html = "<form method='post' action='".route('AdminControllerPostSaveCmsDashboard')."'>";
		$html .= "<input type='hidden' name='type' value='chart_line'/>";
		$html .= "<input type='hidden' name='id' value='$id'/>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>LABEL NAME</label><input required type='text' name='label' value='$content[label]' class='form-control'/>";
		$html .= "</div>";

		$list = array('red','green','yellow','aqua');
		$select = "<select required name='color' class='form-control'>";
		foreach($list as $color) {
			@$selected = ($color==$content['color'])?"selected":"";
			$select .= "<option value='$color' $selected >$color</option>";
		}
		$select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>COLOR</label>$select";
		$html .= "</div>";


		$list = array('half','full-width');
		$select = "<select required name='width' class='form-control'>";
		foreach($list as $l) {
			@$selected = ($l==$content['width'])?"selected":"";
			$select .= "<option value='$l' $selected >$l</option>";
		}
		$select .= "</select>";
		$html .= "<div class='form-group'>";
			$html .= "<label>WIDTH</label>$select";
		$html .= "</div>";

		$list_table = "<select required class='form-control' name='table_name'><option value=''>** Select a Table</option>";

		$tables = list_tables();		
		foreach($tables as $tab) {
			foreach ($tab as $key => $value) {				
				@$selected = ($value == $content['table_name'])?"selected":"";
				$list_table .= "<option $selected value='$value'>$value</option>";
			}
		}

		$list_table .= "</select>";


		$html .= "<div class='form-group'>";
			$html .= "<label>TABLE</label>$list_table";
		$html .= "</div>";

		$aggregate_list = array("count","sum","avg","min","max");
		$aggregate_select = "<select required name='aggregate_type' class='form-control'>";
		foreach($aggregate_list as $list) {
			@$selected = ($list == $content['aggregate_type'])?"selected":"";
			$aggregate_select .= "<option $selected value='$list'>$list</option>";
		}
		$aggregate_select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>AGGREGATE TYPE</label>$aggregate_select";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>COLUMN</label><select name='column' required data-current='$content[column]' class='form-control'></select>";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL WHERE QUERY</label><textarea required name='sql_where' class='form-control'>$content[sql_where]</textarea>";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL GROUP BY</label><textarea required name='sql_group_by' class='form-control'>$content[sql_group_by]</textarea>";
			@$html .= "<div class='help-block'>You can use alias [admin_id_companies],[admin_id]</div>";
		$html .= "</div>";

		$html .= "</form>";

		echo $html;
	}


	public function getSettingChartBar($id='') {
		$row = '';
		@$row = DB::table('cms_dashboard')->where('id',$id)->first();
		@$content = unserialize($row->content);

		$html = "<form method='post' action='".route('AdminControllerPostSaveCmsDashboard')."'>";
		$html .= "<input type='hidden' name='type' value='chart_bar'/>";
		$html .= "<input type='hidden' name='id' value='$id'/>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>LABEL NAME</label><input required type='text' name='label' value='$content[label]' class='form-control'/>";
		$html .= "</div>";

		$color_list = array('red','green','yellow','aqua');
		$color_select = "<select required name='color' class='form-control'>";
		foreach($color_list as $color) {
			@$selected = ($color==$content['color'])?"selected":"";
			$color_select .= "<option value='$color' $selected >$color</option>";
		}
		$color_select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>COLOR</label>$color_select";
		$html .= "</div>";

		$list = array('half','full-width');
		$select = "<select required name='width' class='form-control'>";
		foreach($list as $l) {
			@$selected = ($l==$content['width'])?"selected":"";
			$select .= "<option value='$l' $selected >$l</option>";
		}
		$select .= "</select>";
		$html .= "<div class='form-group'>";
			$html .= "<label>WIDTH</label>$select";
		$html .= "</div>";


		$list_table = "<select required class='form-control' name='table_name'><option value=''>** Select a Table</option>";

		$tables = list_tables();		
		foreach($tables as $tab) {
			foreach ($tab as $key => $value) {				
				@$selected = ($value == $content['table_name'])?"selected":"";
				$list_table .= "<option $selected value='$value'>$value</option>";
			}
		}

		$list_table .= "</select>";


		$html .= "<div class='form-group'>";
			$html .= "<label>TABLE</label>$list_table";
		$html .= "</div>";

		$aggregate_list = array("count","sum","avg","min","max");
		$aggregate_select = "<select required name='aggregate_type' class='form-control'>";
		foreach($aggregate_list as $list) {
			@$selected = ($list == $content['aggregate_type'])?"selected":"";
			$aggregate_select .= "<option $selected value='$list'>$list</option>";
		}
		$aggregate_select .= "</select>";

		$html .= "<div class='form-group'>";
			$html .= "<label>AGGREGATE TYPE</label>$aggregate_select";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>COLUMN</label><select name='column' required data-current='$content[column]' class='form-control'></select>";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL WHERE QUERY</label><textarea required name='sql_where' class='form-control'>$content[sql_where]</textarea>";
			@$html .= "<div class='help-block'>You can use alias [admin_id_companies],[admin_id]</div>";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL GROUP BY</label><textarea required name='sql_group_by' class='form-control'>$content[sql_group_by]</textarea>";
		$html .= "</div>";

		$html .= "</form>";

		echo $html;
	}


	public function getSettingChartDonut($id='') {
		$row = '';
		@$row = DB::table('cms_dashboard')->where('id',$id)->first();
		@$content = unserialize($row->content);

		$html = "<form method='post' action='".route('AdminControllerPostSaveCmsDashboard')."'>";
		$html .= "<input type='hidden' name='type' value='chart_donut'/>";
		$html .= "<input type='hidden' name='id' value='$id'/>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>LABEL NAME</label><input required type='text' name='label' value='$content[label]' class='form-control'/>";
		$html .= "</div>";

		$list = array('half','full-width');
		$select = "<select required name='width' class='form-control'>";
		foreach($list as $l) {
			@$selected = ($l==$content['width'])?"selected":"";
			$select .= "<option value='$l' $selected >$l</option>";
		}
		$select .= "</select>";
		$html .= "<div class='form-group'>";
			$html .= "<label>WIDTH</label>$select";
		$html .= "</div>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>SQL QUERY</label><textarea required rows='6' name='sql_query' class='form-control'>$content[sql_query]</textarea>
			<div class='help-block'>required select label (for label each side chart), value (for value each side chart), color (for color each side chart). Sparate with comma</div>
			";
			@$html .= "<div class='help-block'>You can use alias [admin_id_companies],[admin_id]</div>";
		$html .= "</div>";

		$html .= "</form>";

		echo $html;
	}


	public function getSettingDatatable($id='') {
		$row = '';
		@$row = DB::table('cms_dashboard')->where('id',$id)->first();
		@$content = unserialize($row->content);

		$html = "<form method='post' action='".route('AdminControllerPostSaveCmsDashboard')."'>";
		$html .= "<input type='hidden' name='type' value='datatable'/>";
		$html .= "<input type='hidden' name='id' value='$id'/>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>LABEL NAME</label><input required type='text' name='label' value='$content[label]' class='form-control'/>";
		$html .= "</div>";

		$select = "<select name='id_cms_moduls' class='form-control'>";
		$modules = DB::table('cms_moduls')->orderby('name','asc')->get();
		foreach($modules as $module) {
			$selected = ($module->id == $content['id_cms_moduls'])?"selected":"";
			$select .= "<option $selected value='$module->id'>$module->name</option>";
		}
		$select .= "</select>";

		$html .= "<div class='form-group'>";
			@$html .= "<label>MODULE</label>$select";
		$html .= "</div>";		

		$html .= "<div class='form-group'>";
			@$html .= "<label>LIMIT</label><input required type='number' name='limit' value='$content[limit]' class='form-control'/>";
		$html .= "</div>";

		$html .= "</form>";

		echo $html;
	}


	public function getSelectColumnTable($table,$current='') {
		$cols = DB::getSchemaBuilder()->getColumnListing($table);	
		$html = "";
		foreach($cols as $col) {
			$selected = ($current==$col)?"selected":"";
			$html .= "<option $selected value='$col'>$col</option>";
		}
		echo $html;
	}

	public function getStatisticDashboard($id) {
		$row = DB::table('cms_dashboard')->where('id',$id)->first();
		$content = unserialize($row->content);
		$type = $content['type'];

		switch($type) {
			case "statistic_number":
				$table          = $content['table_name'];
				$label          = $content['label'];
				$aggregate_type = $content['aggregate_type'];
				$column         = $content['column'];
				$sql_where      = $content['sql_where'];				
				$color          = $content['color'];
				@$width         = $content['width'];

				$sql_where 		= ($sql_where)?"and ".str_replace("where ", "", $sql_where):"";
				$sql_where 		= str_ireplace(
						array("[admin_id]","[admin_id_companies]"),
						array(get_my_id(),get_my_id_company()),
						$sql_where
						);				

				switch($aggregate_type) {
					case "count":
						$query = "select count($column) as statistic_total from $table where 1=1 $sql_where";
					break;
					case "sum":
						$query = "select sum($column) as statistic_total from $table where 1=1 $sql_where";
					break;
					case "avg":
						$query = "select avg($column) as statistic_total from $table where 1=1 $sql_where";
					break;
					case "min":
						$query = "select min($column) as statistic_total from $table where 1=1 $sql_where";
					break;
					case "max":
						$query = "select max($column) as statistic_total from $table where 1=1 $sql_where";
					break;
				}

				@$count = DB::select(DB::raw($query))[0]->statistic_total;
				$count  = ($count)?:0;
 				@$icon  = ($content['icon'])?:"ion-stats-bars";
				$html   = "<div class='col-sm-3 dashboard_widget' id='dashboard_$id'>"
		        .'<div class="small-box bg-'.$color.'">'
		        .'<div class="inner">'
		        .'<h3>'.$count.'</h3>'
		        .'<p><a style="color:#fff" title="Edit" class="fa fa-cog btn-edit-stat" data-id="'.$id.'" href="javascript:void(0)"></a> <a title="Delete" style="color:#fff" class="fa fa-trash btn-delete-stat" data-id="'.$id.'" href="javascript:void(0)"></a> '.$label.'</p>'
		        .'</div>'
		        .'<div class="icon">'
		        .'<i class="ion '.$icon.'"></i>'
		        .'</div>'
		        .'</div>';
			break;

			case "chart_line":
				$table          = $content['table_name'];
				$label          = $content['label'];
				$aggregate_type = $content['aggregate_type'];
				$column         = $content['column'];
				$sql_where      = $content['sql_where'];
				$sql_group_by   = $content['sql_group_by'];
				$sql_group_by_alias = explode(' as ',$sql_group_by);
				@$sql_group_by_alias = ($sql_group_by_alias[1])?:$sql_group_by_alias[0];
				$color          = $content['color'];
				$width          = $content['width'];
				$width 			= ($width=='half')?6:12;

				$sql_where 		= ($sql_where)?"and ".str_replace("where ", "", $sql_where):"";
				$sql_where 		= str_ireplace(
						array("[admin_id]","[admin_id_companies]"),
						array(get_my_id(),get_my_id_company()),
						$sql_where
						);

				switch($aggregate_type) {
					case "count":
						$query = "select count($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";						
					break;
					case "sum":
						$query = "select sum($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "avg":
						$query = "select avg($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "min":
						$query = "select min($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "max":
						$query = "select max($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
				}

				$rows = DB::select(DB::raw($query));
				$data = array();
				$data_value = array();
				$data_label = array();
				foreach($rows as $r) {
					$data[] = array("y"=>$r->{$sql_group_by_alias},$label=>$r->statistic_total);
					$data_value[] = $r->statistic_total;
					$data_label[] = $r->{$sql_group_by_alias};
				}
				$data = json_encode($data);
				$data_value = json_encode($data_value);
				$data_label = json_encode($data_label);

		        $html = "
		        <div class='col-sm-$width dashboard_widget'  id='dashboard_$id'>
		        <script>
		        var area = new Morris.Area({
		            element: 'mychart_$id',
		            resize: true,
		            data: $data,
		            xkey: 'y',
		            ykeys: ['$label'],
		            labels: ['$label'],
		            lineColors: ['$color'],
		            parseTime: false,
		            hideHover: 'auto' 
		          });
		        </script>
		        <div class='box box-default'>
			        <div class='box-header with-border'>
			            <h3 class='box-title'><i class='fa fa-bar-chart-o'></i> $label</h3>
			            <div class='box-tools'>  
			            	  <button type='button' title='Edit' class='btn btn-box-tool btn-edit-chart-line' data-id='$id'><i class='fa fa-cog'></i></button>
			            	  <button type='button' title='Delete' class='btn btn-box-tool btn-delete-stat' data-id='$id'><i class='fa fa-times'></i></button>      
			            </div>
			        </div>
			        <div class='box-body'>
			            <div id='mychart_$id' style='height:280px'></div>
			        </div><!-- /.box-body -->
			    </div><!-- /.box -->
			    </div>
			    ";

			break;

			case "chart_bar":
				$table          = $content['table_name'];
				$label          = $content['label'];
				$aggregate_type = $content['aggregate_type'];
				$column         = $content['column'];
				$sql_where      = $content['sql_where'];
				$sql_group_by   = $content['sql_group_by'];
				$sql_group_by_alias = explode(' as ',$sql_group_by);
				@$sql_group_by_alias = ($sql_group_by_alias[1])?:$sql_group_by_alias[0];
				$color          = $content['color'];
				$width          = $content['width'];
				$width 			= ($width=='half')?6:12;

				$sql_where 		= ($sql_where)?"and ".str_replace("where ", "", $sql_where):"";
				$sql_where 		= str_ireplace(
						array("[admin_id]","[admin_id_companies]"),
						array(get_my_id(),get_my_id_company()),
						$sql_where
						);

				switch($aggregate_type) {
					case "count":
						$query = "select count($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "sum":
						$query = "select sum($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "avg":
						$query = "select avg($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "min":
						$query = "select min($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
					case "max":
						$query = "select max($column) as statistic_total,$sql_group_by from $table where 1=1 $sql_where group by $sql_group_by_alias";
					break;
				}

				$rows       = DB::select(DB::raw($query));
				$data       = array();
				$data_value = array();
				$data_label = array();
				foreach($rows as $r) {
					$data[]       = array("y"=>$r->{$sql_group_by_alias},$label=>$r->statistic_total);
					$data_value[] = $r->statistic_total;
					$data_label[] = $r->{$sql_group_by_alias};
				}
				$data       = json_encode($data);
				$data_value = json_encode($data_value);
				$data_label = json_encode($data_label);

		        $html = "
		        <div class='col-sm-$width dashboard_widget'  id='dashboard_$id'>
		        <script>
		        var area = new Morris.Bar({
		            element: 'mychart_$id',
		            resize: true,
		            data: $data,
		            xkey: 'y',
		            ykeys: ['$label'],
		            labels: ['$label'],
		            barColors: ['$color'],
		            hideHover: 'auto' 
		          });
		        </script>
		        <div class='box box-default'>
			        <div class='box-header with-border'>
			            <h3 class='box-title'><i class='fa fa-bar-chart-o'></i> $label</h3>
			            <div class='box-tools'>  
			            	  <button type='button' title='Edit' class='btn btn-box-tool btn-edit-chart-bar' data-id='$id'><i class='fa fa-cog'></i></button>
			            	  <button type='button' title='Delete' class='btn btn-box-tool btn-delete-stat' data-id='$id'><i class='fa fa-times'></i></button>
			            </div>
			        </div>
			        <div class='box-body'>
			            <div id='mychart_$id' style='height:280px'></div>
			        </div><!-- /.box-body -->
			    </div><!-- /.box -->
			    </div>
			    ";

			break;


			case "chart_donut":				
				$label       = $content['label'];				
				$width       = $content['width'];
				$width       = ($width=='half')?6:12;
				$sql_query   = $content['sql_query'];
				$sql_query   = explode(";", $sql_query);
				$colors_arr  = array('#F56954','#3c8dbc','#00a65a','#ffc570','#c8ff70','#ad66ff','#f1ff8e');								

				$data        = array();
				$data_colors = array();
				$ic          = 0;
				foreach($sql_query as $s) {	

					$table = explode('from ',$s);
					$table = explode(' where ',$table[1]);
					$table = $table[0];

					$s 		= str_ireplace(
						array("[admin_id]","[admin_id_companies]"),
						array(get_my_id(),get_my_id_company()),
						$s
						);					

					$query = DB::select(DB::raw($s));										
					foreach($query as $q) {
						@$data[]  = array('label'=>$q->label,'value'=>$q->value);
						if(@$q->color) {
							$data_colors[] = $q->color;
						}else{
							$data_colors[] = $colors_arr[$ic];
							$ic = ($ic>count($colors_arr))?0:$ic+1;
						}
					}
				}
				
				$data_colors = json_encode($data_colors);
				$data        = json_encode($data);

				$html = "	
					<script>				
				    var donut$id = new Morris.Donut({
				      element: 'chart_donut_$id',
				      resize: true,
				      colors: $data_colors,
				      data: $data,
				      hideHover: 'auto'
				    });
					</script>

					<div class='col-sm-$width dashboard_widget' id='dashboard_$id'>
						<!-- DONUT CHART -->
				          <div class='box box-danger'>
				            <div class='box-header with-border'>
				              <h3 class='box-title'><i class='fa fa-bar-chart-o'></i> Donut Chart</h3>

				              <div class='box-tools pull-right'>
				                 <button type='button' title='Edit' class='btn btn-box-tool btn-edit-chart-donut' data-id='$id'><i class='fa fa-cog'></i></button>
			            	     <button type='button' title='Delete' class='btn btn-box-tool btn-delete-stat' data-id='$id'><i class='fa fa-times'></i></button>
				              </div>
				            </div>
				            <div class='box-body chart-responsive'>
				              <div class='chart' id='chart_donut_$id' style='height: 300px; position: relative;'></div>
				            </div>
				            <!-- /.box-body -->
				          </div>
				          <!-- /.box -->

					</div>
				";

			break;

			case "datatable":				
				$label         = $content['label'];				
				$id_cms_moduls = $content['id_cms_moduls'];
				$limit         = $content['limit'];
				$moduls        = DB::table('cms_moduls')->where('id',$id_cms_moduls)->first();
				$main_path     = url(config('crudbooster.ADMIN_PATH').'/'.$moduls->path);
				$path          = ($limit)?$main_path.'?limit='.$limit:$main_path;

				$html = " 
				<div class='col-sm-12 dashboard_widget' id='dashboard_$id'>
			            <div class='box box-primary'>
			                <div class='box-header with-border'>
			                    <h3 class='box-title'><a href='".url($main_path)."' title='Click for more'><i class='fa fa-bars'></i> $label</a></h3>
			                    <div class='box-tools'>  
			                    	<button type='button' title='Edit' class='btn btn-box-tool btn-edit-datatable' data-id='$id'><i class='fa fa-cog'></i></button>
			            	     <button type='button' title='Delete' class='btn btn-box-tool btn-delete-stat' data-id='$id'><i class='fa fa-times'></i></button>          
			                    </div>
			                </div>
			                <div id='table_data_$id' class='box-body table-responsive no-padding'>
			                        <div style='padding:10px'><i class='fa fa-spinner fa-spin'></i> Please wait loading datatable $label...</div>
			                </div><!-- /.box-body -->
			            </div><!-- /.box -->			        
			      </div>
			      <script>
			        $(function() {
			            $.get('$path',function(htm) {                
			                var raw = $('<div>').append($(htm).find('#table_dashboard').clone()).html();                
			                $('#table_data_$id').html(raw);
			            })
			        })
			    </script>
				";
		}		
		echo $html;
	}

	public function postSaveCmsDashboard() {
		$post                   = Request::all();
		$post['id'] 			= intval($post['id']);
		$a                      = array();
		$a['name']              = $post['label'];
		$a['content']           = serialize($post);
		$a['id_cms_privileges'] = intval(Session::get('dashboard_config_id_privileges'));
		if($post['id']) {
			DB::table('cms_dashboard')->where('id',$post['id'])->update($a);
			$lastId = $post['id'];
		}else{
			DB::table('cms_dashboard')->insert($a);	
			$lastId = DB::getPdo()->lastInsertId();
		}		
		
		echo $lastId;
	}

	public function getRemoveCmsDashboard($id) {
		DB::table('cms_dashboard')->where('id',$id)->delete();
		echo 1;
	}


	public function getLockscreen() {
		
		if(!get_my_id()) {
			Session::flush();
			return redirect()->route('getLogin')->with('message','Your session was expired, please login again !');
		}
		
		Session::put('admin_lock',1);
		return view('crudbooster::lockscreen');
	}

	public function postUnlockScreen() {
		$id       = get_my_id();
		$password = Request::input('password');		
		$users    = DB::table('cms_users')->where('id',$id)->first();		

		if(\Hash::check($password,$users->password)) {
			Session::put('admin_lock',0);	
			return redirect()->route('AdminControllerGetIndex'); 
		}else{
			echo "<script>alert('Sorry, Your password is wrong !');history.go(-1);</script>";				
		}
	}

	public function getLogin()
	{					
		$data['page_title'] = "<b>Login</b> Page";

		$privileges_register = DB::table('cms_privileges')->where('is_register',1)->get();
		$data['privileges_register'] = $privileges_register;

		return view('crudbooster::login',$data);
	}
 
	public function postLogin() {		

		$validator = Validator::make(Request::all(),			
			[
			'email'=>'required|email|exists:cms_users',
			'password'=>'required'			
			]
		);
		
		if ($validator->fails()) 
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}

		$email 		= Request::input("email");
		$password 	= Request::input("password");
		$users 		= DB::table('cms_users')->where("email",$email)->first(); 		

		if(\Hash::check($password,$users->password)) {
			$priv = DB::table("cms_privileges")->where("id",$users->id_cms_privileges)->first();
			
			$photo = ($users->photo)?asset($users->photo):'https://www.gravatar.com/avatar/'.md5($users->email).'?s=100';
			Session::put('admin_id',$users->id);
			Session::put('admin_id_companies',$users->id_cms_companies);
			Session::put('admin_is_superadmin',$priv->is_superadmin);
			Session::put('admin_name',$users->name);	
			Session::put('admin_photo',$photo);
			Session::put("admin_privileges",$users->id_cms_privileges);
			Session::put('admin_privileges_name',$priv->name);	
			Session::put('admin_lock',0);
			Session::put('theme_color',$priv->theme_color);
			Session::put("appname",$this->appname);			

			return redirect()->route('AdminControllerGetIndex'); 
		}else{
			return redirect()->route('getLogin')->with('message', 'Sorry your password is wrong !');			
		}		
	}

	public function getForgot() {		
		return view('crudbooster::forgot');
	}

	public function postForgot() {
		$validator = Validator::make(Request::all(),			
			[
			'email'=>'required|email|exists:cms_users'			
			]
		);
		
		if ($validator->fails()) 
		{
			$message = $validator->errors()->all();
			return redirect()->back()->with(['message'=>implode(', ',$message),'message_type'=>'danger']);
		}	

		$rand_string = str_random(5);
		$password = \Hash::make($rand_string);

		DB::table('cms_users')->where('email',Request::input('email'))->update(array('password'=>$password));
 	
 		$appname = get_setting('appname');
		$user             = DB::table('cms_users')->where("email",Request::input('email'))->first();
		$data             = array();
		$data['email']    = $user->email;
		$data['password'] = $rand_string;

		$html = "
			Hi $user->name, <br/>
			We're heard that you requested password, this bellow is your new password :<br/>
			<h3>$rand_string</h3><br/>
			You can use this password to login at $appname					
		";

		send_email($user->email,"Forgot Password $appname",$html);

		return redirect()->route('getLogin')->with('message', 'We have sent new password to your email, check inbox or spambox !');

	}	

	public function getRegister() {
		$privileges_register = DB::table('cms_privileges')->where('is_register',1)->get();
		$data['privileges_register'] = $privileges_register;

		if(count($data['privileges_register']) == 0) return redirect()->route('getLogin')->with('message','Sorry, unfortunately register is closed !');

		return view('crudbooster::register',$data);
	}

	public function getUsersConfirmation($hash_confirmation) {
		if(DB::table('cms_users')->where('hash_confirmation',$hash_confirmation)->count()) {
			DB::table('cms_users')->where('hash_confirmation',$hash_confirmation)->update(['status'=>'Active','hash_confirmation'=>NULL]);
			return redirect()->route('getLogin')->with('message','Thanks for email confirmation. You can sign in !');
		}else{
			return rediirect()->route('getLogin')->with('message','Email confirmation failed, please make sure the URL is correct, if this issue continue, please contact Administrator');
		}
	}

	public function postRegister() {
		valid(['name','email'=>'unique:cms_users,email','password'],NULL,'view'); 

		$is_confirmation = false;
		$is_welcome = false;

		$post = Request::all();
		unset($post['_token']);
		$post['created_at'] = date('Y-m-d H:i:s');
		$post['password'] = Hash::make($post['password']);

		/* Send email confirmation if setted */
		if($this->setting->register_email_confirmation!='') {
			$post['status'] = 'Not Active';
			$to = $post['email'];
			$subject = 'Register Confirmation Email at '.$this->setting->appname;

			$html = $this->setting->register_email_confirmation;
			foreach($post as $key=>$val) {
				$html = str_replace("[".$key."]",$val,$html);
			}

			$hash_confirmation = str_random(12);
			$post['hash_confirmation'] = $hash_confirmation;
			$link_confirmation = route('AdminControllerGetUsersConfirmation').'/'.$hash_confirmation;

			$html = str_replace("[link_confirmation]","<a href='$link_confirmation'>$link_confirmation</a>",$html);

			send_email($to,$subject,$html);
			$is_confirmation = true;

		}else{
			$post['status'] = 'Active';
		}

		/* Save data users */
		DB::table('cms_users')->insert($post);

		/* Send email welcome after register if any */
		if($this->setting->register_email_welcome!='') {
			$to = $post['email'];
			$subject = 'Welcome Registration at '.$this->setting->appname;

			$html = $this->setting->register_email_welcome;
			foreach($post as $key=>$val) {
				$html = str_replace("[".$key."]",$val,$html);
			}

			send_email($to,$subject,$html);
			$is_welcome = true;
		}

		if($is_confirmation) {
			return redirect()->route('getLogin')->with('message','Thanks for register, please check your email at inbox or spambox. We have sent an email confirmation, follow the instruction at email');	
		}else{
			return redirect()->route('getLogin')->with('message','Thanks for register, please sign in to start your session');	
		}
		
	}
	
	public function getLogout() {
		Session::flush();

		return redirect()->route('getLogin')->with('message','Thank You, See You Later !');
	}

}
