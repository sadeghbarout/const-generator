<?php
namespace Colbeh\Consts\Controllers;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ConstController {


	public function  index() {

        $dbName = env('DB_DATABASE');
        $tablesRaw = DB::select('SHOW TABLES');

        $tables = [];
        $tablesIn = 'Tables_in_'.$dbName;
        foreach($tablesRaw as $table){
            $tables[] = $table -> $tablesIn;
        }
		return view("const::index", compact('tables'));
    }


	// ---------------------------------------------------------------------------------------------
	public function  add() {
		$modelName=request('modalName');
		$table=request('table');
		$colPrefix=request('colPrefix');
		$cols=request('cols');
        $withController=request('withController');


        list($data,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames)=$this->createData($modelName,$table,$colPrefix,$cols);

		 $this->writeConsts($data,'table');
		 $this->writeMigration($table,$tableName,$migrateData);
		 $this->writeModel($modelName,$scopeData,$scopeHintData);
		 $this->writeFactory($modelName,$factoryData);
		 $this->writeController($withController,$modelName,$colNames);


		if($withController){
            return redirect() -> back() -> with('status', $modelName.' model,consts,migration,controller,route and facrory successfully generated!');
		}else{
            return redirect() -> back() -> with('status', $modelName.' model,consts,migration and facrory successfully generated!');
		}
    }


    // ---------------------------------------------------------------------------------------------
	public function createData($modalName,$table,$colPrefix,$cols) {
		$cols=explode("\r\n",$cols);

		$colNames = [];
		$tableName="TBL_".strtoupper($table);

		$constData="define('".$tableName."', '".strtolower($table)."');\n";
		$factoryData = "";
		$migrateData="";
		$scopeData="use HasFactory;";
		$scopeHintData='/**
 * App\\'.$modalName.'
 *
 ';

		foreach ($cols AS $col){

			list($hasScope,$col)=$this->checkHasScope($col);

			list($colType,$col)=$this->getType($col);

			$colName='COL_'.strtoupper($colPrefix).'_'.strtoupper($col); // first_name => COL_USER_FIRST_NAME
			$ucaseCol=str_replace('_', '', ucwords($col, '_')); // first_name =>FistName
			$camelcaseCol=lcfirst($ucaseCol); // FistName=> fistName

			$colNames[$col] = $colName;
			$constData.="define('$colName', '".strtolower($col)."');\n";
			$factoryData .= $this -> generateFactoryRow($colType,$colName);
			$migrateData .= $this -> generateMigrationRow($colType,$colPrefix, $colName,$col);

			if($hasScope){
				$scopeHintData .= $this -> generateScopeHintRow($modalName, $camelcaseCol);
				$scopeData .= $this -> generateScopeFunction($ucaseCol, $tableName, $colName);
			}
		}

		$scopeHintData.='
		*/';

		$constData.="\n\n\n\n";

		return array($constData,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames);
    }


	private function checkHasScope($col) {
		$hasScope=false;
		if(strpos($col,'*')){
			$hasScope=true;
			$col=str_replace('*','',$col);
		}

		return [$hasScope,$col];

	}

	private function getType($col) {
		$colType="";
		if($col == "id")
			$colType="id";

		elseif(strpos($col,'=')){
			$colType = substr($col, strpos($col, "=") + 1);
			$colType=$colType=='int'?'integer':$colType;
			$colType=$colType=='str'?'string':$colType;

			if($colType == "")
				dd("Error : Enter ".$col." Type");

			$col = substr($col, 0, strpos($col, "="));

		}else{
			dd("Error : Enter ".$col." Type");
		}

		return [$colType,$col];
    }

	// ---------------------------------------------------------------------------------------------
	public function generateFactoryRow($colType,$colName){
		if($colType == 'string'){
			$value = '$this->faker->name';
		}
		elseif($colType == 'text'){
			$value = '$this->faker->text(100)';
		}
		elseif($colType == 'integer'){
			$value = 'mt_rand(1,20)';
		}
		elseif($colType == 'tinyint'){
			$value = 'mt_rand(0,1)';
		}
		elseif($colType == 'bigint'){
			$value = 'mt_rand(0,1000)';
		}
		elseif($colType == 'double'){
			$value = 'mt_rand(1000,2000)';
		}
		elseif($colType == 'phone'){
			$value = '$this->faker->phoneNumber';
		}
		elseif($colType == 'email'){
			$value = '$this->faker->name.mt_rand(1,20)."@gmail.com"';
		}
		elseif($colType == 'username'){
			$value = '$this->faker->userName';
		}
		elseif($colType == 'datetime'){
			$value = 'getServerDateTime()';
		}
		elseif($colType == 'password'){
			$value = 'bcrypt("123456")';
		}elseif($colType == 'time'){
			$value = '$this->faker->time($format = "H:i:s", $max = "now")';
		}elseif($colType == 'date'){
			$value = '$this->faker->date()';
		}elseif($colType == 'bool'){
			$value = 'mt_rand(0,1)';
		}
		elseif(strpos($colType,'enum') !== false){
			$colType = str_replace("enum:","",$colType);
			$enums = explode(',',$colType);
			$value = "rand(1,".sizeof($enums).")";
		}

		if(isset($value)){
			return  $colName.' => '.$value.','."\n";
		}

		return '';
	}


	// ---------------------------------------------------------------------------------------------
	public function generateMigrationRow($colType, $colPrefix, $colName, $col){

		if($colType == 'id'){
			$value ='increments('.$colName.');';
		}
		elseif($colType == 'string'){
			$value = 'string('.$colName.',250) -> nullable();';
		}
		elseif($colType == 'password'){
			$value = 'string('.$colName.',250);';
		}
		elseif($colType == 'text'){
			$value = 'text('.$colName.') -> nullable();';
		}
		elseif($colType == 'integer'){
			$value = 'integer('.$colName.') -> nullable();';
		}
		elseif($colType == 'tinyint'){
			$value = 'tinyInteger('.$colName.') -> default(0);';
		}
		elseif($colType == 'bigint'){
			$value =  'bigInteger('.$colName.') -> default(0);';
		}
		elseif($colType == 'double'){
			$value = 'double('.$colName.');';
		}
		elseif($colType == 'phone'){
			$value = 'string('.$colName.',20) -> nullable();';
		}
		elseif($colType == 'email'){
			$value = 'string('.$colName.',50) -> nullable();';
		}
		elseif($colType == 'username'){
			$value = 'string('.$colName.',150);';
		}
		elseif($colType == 'datetime'){
			$value = 'datetime('.$colName.') -> nullable();';
		}elseif($colType == 'time'){
			$value = 'time('.$colName.') -> nullable();';
		}elseif($colType == 'date'){
			$value = 'date('.$colName.') -> nullable();';
		} elseif($colType == 'bool'){
			$value = 'boolean('.$colName.') ->default(false);';
		}
		elseif(strpos($colType,'enum') !== false){
			$colType = str_replace("enum:","",$colType);
			$enums = explode(',',$colType);
			$enumConsts = $this -> enumConstGenerator($enums,$colPrefix,$col);
			$value = 'enum('.$colName.',['.$enumConsts.']);';
		}

		if(isset($value)){
			return '$table->'.$value."\n";
		}

		return '';
	}


	// ---------------------------------------------------------------------------------------------
	public function enumConstGenerator($enums,$colPrefix,$col){
		$data1= "";
		$enumConsts = [];
		foreach($enums as $enum){
			$enumName='ENUM_'.strtoupper($colPrefix).'_'.strtoupper($col).'_'.strtoupper($enum);
			$enumConsts[] = $enumName;
			$data1.="define('$enumName', '".strtolower($enum)."');\n";
		}
		$data1.="\n\n\n";
		$this->writeConsts($data1,'enum');
		$enumConsts = implode(',',$enumConsts);

		return $enumConsts;
	}
	// ---------------------------------------------------------------------------------------------
	private function generateScopeHintRow($modalName, $camelcaseCol) {
		return ' * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\\' . $modalName . ' ' . $camelcaseCol . '($value)
 ';
	}

	// ---------------------------------------------------------------------------------------------
	private function generateScopeFunction($ucaseCol, $tableName, $colName) {
		return '
	/* @param \Illuminate\Database\Eloquent\Builder $query */
		public function scope' . $ucaseCol . '($query, $value) {
			if (ModelEnhanced::checkParameter($value)) {
				return $query->where(' . $tableName . '.".".' . $colName . ',$value);
			}
			return $query;
		}

	';
	}
    // ---------------------------------------------------------------------------------------------
	public function writeConsts($data, $tableOrEnum='table') {

		$constsPath='../app/Extras/consts.php';
		$charsCountAfterHeader=218;
		$constsFileContent=file_get_contents($constsPath);
		$pos=0;
		if($tableOrEnum=='table'){
			$pos=strpos($constsFileContent,"   DATABASES   ");
		}elseif($tableOrEnum=='enum'){
			$pos=strpos($constsFileContent,"   ENUMS   ");
		}
		$constsFileContent=substr_replace($constsFileContent,$data,$pos+$charsCountAfterHeader,0);
		file_put_contents($constsPath,$constsFileContent);
	}


    // ---------------------------------------------------------------------------------------------
	public function writeMigration($table, $tableName, $migrateData) {
		Artisan::call("make:migration create_".$table."_table --create=".$table);

		$output=Artisan::output();
		$migrationName=explode(" ",str_replace("\r\n","",$output))[2];
		$migrationPath='../database/migrations/'.$migrationName.'.php';
		$migrationFileContent=file_get_contents($migrationPath);
		$migrationFileContent=str_replace("'".strtolower($table)."'",$tableName,$migrationFileContent);
		$migrationFileContent=str_replace('$table->id();',$migrateData,$migrationFileContent);
		file_put_contents($migrationPath,$migrationFileContent);
	}


    // ---------------------------------------------------------------------------------------------
	public function writeModel($modalName, $scopeData, $scopeHintData) {
		Artisan::call("make:model $modalName");

		$modelPath='../app/models/'.$modalName.'.php';
		$modelFileContent=file_get_contents($modelPath);
		$modelFileContent=str_replace('use HasFactory;',$scopeData,$modelFileContent);
		$modelFileContent=substr_replace($modelFileContent,$scopeHintData,125,0);
		$modelFileContent=substr_replace($modelFileContent,'use App\ModelEnhanced;',29,0);

		$ModelPos=strpos($modelFileContent,'Model',strpos($modelFileContent,'extends'));
		$modelFileContent=substr_replace($modelFileContent,'ModelEnhanced',$ModelPos,5);

		file_put_contents($modelPath,$modelFileContent);
	}


    // ---------------------------------------------------------------------------------------------
	public function writeFactory($modalName, $factoryData) {
		Artisan::call("make:factory ".$modalName."Factory --model=".$modalName);
		$factoryPath='../database/factories/'.$modalName.'Factory.php';
		$factoryFileContent=file_get_contents($factoryPath);
		$factoryFileContent=str_replace('//',$factoryData,$factoryFileContent);
		file_put_contents($factoryPath,$factoryFileContent);
	}


	// ---------------------------------------------------------------------------------------------
	public function writeController($withController, $modelName, $colNames) {
		if ($withController != null) {
			Artisan::call("make:controller $modelName" . "Controller -r");
			$controllerName = $modelName . "Controller";
			$this->fillControllerMethods($modelName, $colNames);
			$this->createRoute($modelName, $controllerName);
		}
	}


    // ---------------------------------------------------------------------------------------------
    public function fillControllerMethods($modelName,$cols){
        $controllerPath = '../app/Http/Controllers/'.$modelName.'Controller.php';
        $content = file_get_contents($controllerPath);

        $colsData = "";
        foreach($cols as $index => $col){
            if($index !=  "id")
                $colsData .= '          $c['.$col.'] = request('.$col.');'."\n";
        }


        // ---------------------------------------------------------- use Model
        $indexPos = strpos($content,'use Illuminate\Http\Request;');
        $indexOp = 'use App\Models\\'.$modelName.';';
        $content = substr_replace($content,$indexOp,$indexPos-1,0);


        // ---------------------------------------------------------- index
        $indexPos = strpos($content,'index(');
        $indexOperationPos = strpos($content,'//',$indexPos);
        $indexOp = '$rowsCount = request("rows_count",10);
		$page = request("page", 1);

		$builder = '.$modelName.'::id("");
		$count = $builder->count();
		$items = $builder->orderByDesc(\'id\')->page2($page, $rowsCount)->get();

		$pageCount = ceil($count/$rowsCount);

		return generateResponse(RES_SUCCESS,array(\'items\' => $items , \'page_count\' => $pageCount));
        ';
        $content = substr_replace($content,$indexOp,$indexOperationPos,2);


        // ---------------------------------------------------------- store
        $storePos = strpos($content,'store(');
        $storeOperationPos = strpos($content,'//',$storePos);
        $storeOp = '
            $item = new '.$modelName.'();
'.$colsData.'
            $item -> save();
        ';
        $content = substr_replace($content,$storeOp,$storeOperationPos,2);


        // ------------------------------------------------------------- update
        $updatePos = strpos($content,'update(');
        $updateOperationPos = strpos($content,'//',$updatePos);
        $updateOp = '
            $item = '.$modelName.'::findOrError($id);
'.$colsData.'
            $item -> save();
        ';
        $content = substr_replace($content,$updateOp,$updateOperationPos,2);


        // ------------------------------------------------------------- destroy
        $destroyPos = strpos($content,'destroy(');
        $destroyOperationPos = strpos($content,'//',$destroyPos);
        $destroyOp = '
            $item = '.$modelName.'::findOrError($id);
            $item -> delete();
        ';
        $content = substr_replace($content,$destroyOp,$destroyOperationPos,2);

        // ------------------------------------------------------------- show
        $showPos = strpos($content,'show(');
        $showOperationPos = strpos($content,'//',$showPos);
        $showOp = '
            $item = '.$modelName.'::findOrError($id);
            return generateResponse(RES_SUCCESS,["item"=>$item]);
        ';
        $content = substr_replace($content,$showOp,$showOperationPos,2);


		// put content in file
		file_put_contents($controllerPath,$content);

        return;
    }


    // ---------------------------------------------------------------------------------------------
	public function createRoute($modelName,$controllerName) {
		$routePath='../routes/web.php';
		$routeData='
	Route::resource("'.lcfirst($modelName).'","'.$controllerName.'");';
		file_put_contents($routePath,$routeData,FILE_APPEND);

	}


    // -------------------------------------------------------------------------------------------------------------
    public function columnAdd(){
        $table = request('table');
        $cols = request('cols');
        $data = '';


        $tableName="TBL_".strtoupper($table);


        $constsPath='../app/Extras/consts2.php';
        $constsFileContent=file_get_contents($constsPath);
        $pos=0;

        $pos=strpos($constsFileContent, "define('".$tableName."', '".$table."')");
        $pos=strpos($constsFileContent, "\n",$pos);


        $colSample = \substr($constsFileContent, $pos+1, strpos($constsFileContent, "\n",$pos+1) - $pos);

        // extracting the column name and make it upper case : id => ID
        $colSampleName = substr($colSample, strpos($colSample, ","));
        $colSampleName = \str_replace(',','',$colSampleName);
        $colSampleName = \str_replace("'",'',$colSampleName);
        $colSampleName = \str_replace(")",'',$colSampleName);
        $colSampleName = \str_replace("\n",'',$colSampleName);
        $colSampleName = \str_replace(";",'',$colSampleName);
        $colSampleName = strtoupper($colSampleName);
        $colSampleName = trim($colSampleName);


        // extracting something like this : COL_BANK_ID    and turn it to : COL_BANK_   as the prefix
        $colSamplePrefix = substr($colSample, strpos($colSample, "("),  strpos($colSample, ",") - strpos($colSample, "("));
        $colSamplePrefix = \str_replace('(','',$colSamplePrefix);
        $colSamplePrefix = \str_replace("'",'',$colSamplePrefix);
        $colSamplePrefix = \str_replace("'",'',$colSamplePrefix);
        $colSamplePrefix = substr($colSamplePrefix,0, strpos($colSamplePrefix,$colSampleName));
        $colSamplePrefix = \str_replace('COL_','',$colSamplePrefix);
        $colSamplePrefix = \str_replace('_','',$colSamplePrefix);




        $model = Str::studly(Str::singular($table));
        $modelDirectory = 'App\Models\\' . $model;
        if(!class_exists($modelDirectory)) {
            dd('Model not found'); // TODO
        }


        // creating the new column constant : COL_BANK_NEW
        list($data,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames) = $this->colsDataCreation($cols,$colSamplePrefix, $table, $model);
        $data = trim($data);
        $constsFileContent=substr($constsFileContent,0,$pos+1).$data."\n".substr($constsFileContent,$pos+1);




        // finding migration file
        $migFile = DB::table('migrations')->where('migration','LIKE',"%$table%")->first();
        if($migFile == null)
            dd('ERROR : migration file not found in the database migration table');

        $migFilePath='../database/migrations/'.$migFile->migration.'.php';
        $migFileContent = file_get_contents($migFilePath);


        $tPos1 = strpos($migFileContent,'$table', strpos($migFileContent, '$table') + 1); // TODO : find last $table
        $tPos2 = strpos($migFileContent,"\n",$tPos1);
        $migFileContent=substr($migFileContent,0,$tPos2+1).$migrateData."\n".substr($migFileContent,$tPos2+1);



        // factory
        $factoryPath='../database/factories/'.$model.'Factory.php';
        $factoryFileContent=file_get_contents($factoryPath);
        $facPos1 = strpos($factoryFileContent, "]");
        $factoryFileContent=substr($factoryFileContent,0,$facPos1-1).$factoryData."\n".substr($factoryFileContent,$facPos1-1);
        $factoryFileContent = trim($factoryFileContent);



        // model
        if($scopeData != ""){
            // TODO
        }





        // file_put_contents($constsPath,$constsFileContent);
        // file_put_contents($migFilePath,$migFileContent);
        // file_put_contents($factoryPath,$factoryFileContent);

        // DB::statement('ALTER TABLE '.$table.' ADD new1 int');
        dd('asd');

    }










    // ---------------------------------------------------------------------------------------
    public function colsDataCreation($cols,$colPrefix,$tableName,$modalName){
        $cols=explode("\r\n",$cols);
        $data = '';
        $migrateData = '';
        $scopeHintData = '';
        $scopeData = '';
        $factoryData  = '';


        foreach ($cols AS $col){

			$hasScope=false;
			if(strpos($col,'*')){
				$hasScope=true;
				$col=str_replace('*','',$col);

			}

			if(strpos($col,'=')){
                $colType = substr($col, strpos($col, "=") + 1);
                $colType = trim($colType);
				$colType=$colType=='int'?'integer':$colType;
				$colType=$colType=='str'?'string':$colType;
                $col = substr($col, 0, strpos($col, "="));

                if($colType == "")
                    dd("Error : Enter ".$col." Type");
            }
            else{
                if($col != "id")
                    dd("Error : Enter ".$col." Type");
            }
            $col = trim($col);
			$colName='COL_'.strtoupper($colPrefix).'_'.strtoupper($col);
			$colNames[$col] = $colName;
			$data.="define('$colName', '".strtolower($col)."');\n";



			if($col=='id'){
				$migrateData.='$table->increments('.$colName.');'."\n";
			}else{

                $colType = trim($colType);
                $colPrefix = trim($colPrefix);
                $colName = trim($colName);
                $col = trim($col);

				$migrateData .= $this -> generateMigrationRow($colType,$colPrefix, $colName,$col);
				$factoryData .= $this -> generateFactoryRow($colType,$colName);
			}



			if($hasScope){

				$ucaseCol=str_replace('_', '', ucwords($col, '_'));;
				$camelcaseCol=lcfirst($ucaseCol);
				$scopeHintData.=' * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\\'.$modalName.' '.$camelcaseCol.'($value)
 ';
				$scopeData.='
	/* @param \Illuminate\Database\Eloquent\Builder $query */
		public function scope'.$ucaseCol.'($query, $value) {
			if (ModelEnhanced::checkParameter($value)) {
				return $query->where('.$tableName.'.".".'.$colName.',$value);
			}
			return $query;
		}

	';
			}

		}

		$scopeHintData.='
        */';



        $data.="\n\n\n\n";

		return array($data,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames);
    }


	// ---------------------------------------------------------------------------------------

	public function casts(){
		$files = scandir(app_path('../database/migrations'));
		$allcasts = '';
		$cols = [];

		foreach($files as $file){
			if($file != '.' && $file != '..' ){
				$m = file_get_contents(app_path('../database/migrations/'.$file));
				$pos = 0;
				do{
					$pos = strpos($m,'integer(',$pos+1);
					if($pos != false){
						$x = substr($m,$pos+8,strpos($m,")",$pos)-$pos-8);
						$cols[] = $x;
					}
					else{
						break;
					}
				}while($pos != -1);




			}

		}
		if(sizeof($cols) > 0){
			$str = 'protected $casts = [
                        ';
			foreach($cols as $col){
				$str .= $col .'=> "integer",
                    ';
			}
			$str.= '
                ];

                ';

			$allcasts .= $str;
		}
		return $allcasts;

	}


}



