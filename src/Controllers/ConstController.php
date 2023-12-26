<?php
namespace Colbeh\Consts\Controllers;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Colbeh\Consts\Helper;

class ConstController {

	const constsPath="../app/Extras/consts.php";
	const migrationsPath='../database/migrations/';
	const modelsPath='../app/models/';
	const factoriesPath='../database/factories/';
	const controllersPath='../app/Http/Controllers/';
	const routesPath='../routes/web.php';
	const dbStructureJsonFilePath='../resources/files/database-structure.json';
	const utilsJsonFilePath='../resources/files/utils.json';

	public function  index() {
		return view("builder::index");
    }


    // ---------------------------------------------------------------------------------------------
    public function column(){
        $dbName = config('database.connections.mysql.database');
        $tablesRaw = DB::select('SHOW TABLES');

        $tables = [];
        $tablesIn = 'Tables_in_'.$dbName;
        foreach($tablesRaw as $table){

            $tableName = $table -> $tablesIn;

            $tableColumns = DB::select("SHOW COLUMNS FROM ". $tableName);

            $tables[$tableName]['table_name'] = $tableName;

            foreach($tableColumns as &$tc){
                $tc = $this->convertDatabaseColumnTypeToOurStandard($tc);
            }

            $tables[$tableName]['cols'] = $tableColumns;
        }
        $tables = json_encode($tables);

        return view("builder::newColumn", compact('tables'));
    }


	// ---------------------------------------------------------------------------------------------
	public function  add() {
		$modelName=request('modalName');
		$table=request('table');
		$colPrefix=request('colPrefix');
		$cols=request('cols');
        $withController=request('withController');


        list($data,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames, $dbStructureJsonForVue, $utilsJsonFileData, $accessorsData, $controllerIndexFilterVariables, $controllerIndexFilterScopes)=$this->createData($modelName,$table,$colPrefix,$cols);

        $this->writeConsts($data,'table');
        $this->writeMigration($table,$tableName,$migrateData);
        $this->writeModel($modelName,$scopeData,$scopeHintData, $accessorsData);
        $this->writeFactory($modelName,$factoryData);
        $this->writeController($withController,$modelName,$colNames, $controllerIndexFilterVariables, $controllerIndexFilterScopes);
//        $this->writeDbStructureJson($dbStructureJsonForVue);
        $this->writeUtilsJson($utilsJsonFileData);



		if($withController){
            return Helper::sucBack($modelName.' model,consts,migration,controller,route and facrory successfully generated!');
		}else{
            return Helper::sucBack($modelName.' model,consts,migration and facrory successfully generated!');
		}
    }


    // ---------------------------------------------------------------------------------------------
	public function createData($modalName,$table,$colPrefix,$cols) {
		$cols=explode("\n",$cols);


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

        $dbStructureJsonForVue = [
            "name"=> $table,
            "model_name"=> $modalName,
            "cols"=>[],
        ];
        $utilsJsonFileData = [];
        $accessorsData = "";
        $controllerIndexFilterVariables = "";
        $controllerIndexFilterScopes = "";

		foreach ($cols AS $col){


			list($hasScope,$col)=$this->checkHasScope($col);

			list($colType,$col,$enumsArray)=$this->getType($col);



			$colName='COL_'.strtoupper($colPrefix).'_'.strtoupper($col); // first_name => COL_USER_FIRST_NAME
			$ucaseCol=str_replace('_', '', ucwords($col, '_')); // first_name =>FistName
			$camelcaseCol=lcfirst($ucaseCol); // FistName=> fistName

			$colNames[$col] = [
                "const"=>$colName,
                "type"=>$colType,
            ];
			$constData.="define('$colName', '".strtolower($col)."');\n";
			$factoryData .= $this -> generateFactoryRow($colType,$colName,$enumsArray);
			$migrateData .= $this -> generateMigrationRow($colType,$colPrefix, $colName,$col,$enumsArray);

			if($hasScope){
				$scopeHintData .= $this -> generateScopeHintRow($modalName, $camelcaseCol);
				$scopeData .= $this -> generateScopeFunction($ucaseCol, $tableName, $colName);
                $controllerIndexFilterVariables .= "$".$camelcaseCol."= request('$col');".PHP_EOL;
                $controllerIndexFilterScopes .= $camelcaseCol."($".$camelcaseCol.")->";
			}



            $dbStructureJsonForVue["cols"][] = [
                "name" => $col,
                "db_type" => $colType,
//                "html_type" => $enumsArray != null ? 'select' : $this->getColHTMLType($colType)[0],
//                "in_index_table" => $enumsArray != null ? 1 : $this->getColHTMLType($colType)[1],
                "enum_values" => $enumsArray != null ? $enumsArray : '',
            ];


            $utilsJsonFileData = $this->generateEnumsUtilsJsonFileData($utilsJsonFileData, $enumsArray, $modalName, $col);

            $accessorsData = $this->generateAccessorsData($accessorsData, $enumsArray, $modalName, $col, $colName, $colType);

		}

		$scopeHintData.='
		*/';


        $controllerIndexFilterScopes = rtrim($controllerIndexFilterScopes, '->');


        //        creating COL_CREATED_AT AND COL_UPDATED_AT
        $createdAtConstName = "COL_".strtoupper($colPrefix)."_CREATED_AT";
        $updatedAtConstName = "COL_".strtoupper($colPrefix)."_UPDATED_AT";

        $accessorsData = $this->generateAccessorsData($accessorsData, null, $modalName, "created_at", $createdAtConstName, "datetime");
        $accessorsData = $this->generateAccessorsData($accessorsData, null, $modalName, "updated_at", $updatedAtConstName, "datetime");

        $constData.="define('$createdAtConstName', 'created_at');\n";
        $constData.="define('$updatedAtConstName', 'updated_at');\n";
        //


        $constData.="\n\n\n\n";

		return array($constData,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames, $dbStructureJsonForVue, $utilsJsonFileData, $accessorsData, $controllerIndexFilterVariables, $controllerIndexFilterScopes);
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
		$enumsArray=null;



		if($col == "id")
			$colType="id";


		elseif(strpos($col,'=')){

            $colType = substr($col, strpos($col, "=") + 1);
			$colType=$colType=='int'?'integer':$colType;
			$colType=$colType=='str'?'string':$colType;

			if(strpos($colType,"enum") !== false) {
				$currentType = \str_replace("enum:", "", $colType);
				$enumsArray = explode(",", $currentType);
			}

			if($colType == ""){
                Helper::errorBack("Error : Enter ".$col." Type");
            }

			$col = substr($col, 0, strpos($col, "="));

		}else{
            Helper::errorBack("Error : Enter ".$col." Type");
		}

		return [$colType,$col,$enumsArray];
    }


    private function getColHTMLType($colType){

        // "db_type" => ["html_type", "should_be_in_index"]

        $htmlTypes = [
            "id" => ['number', 1],
            "string" => ['text', 1],
            "text" => ['textarea', 0],
            "integer" => ['number', 1],
            "tinyint" => ['number', 1],
            "bigint" => ['number', 1],
            "double" => ['number', 1],
            "phone" => ['text', 1],
            "email" => ['email', 1],
            "username" => ['text', 1],
            "datetime" => ['date', 0],
            "password" => ['password', 0],
            "time" => ['date', 0],
            "date" => ['date', 0],
            "bool" => ['checkbox', 1],
            "file" => ['file', 0],
            "image" => ['file', 0],
        ];

        return $htmlTypes[$colType];
    }


	// ---------------------------------------------------------------------------------------------
	public function generateFactoryRow($colType,$colName,$data=null){
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
		elseif($colType == 'datetime' || $colType == 'timestamp'){
			$value = 'getServerDateTime()';
		}
		elseif($colType == 'file' || $colType == 'image'){
			$value = '$this->faker->text(100)';
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
			$value = "rand(1,".sizeof($data).")";

		}

		if(isset($value)){
			return  $colName.' => '.$value.','."\n";
		}

		return '';
	}


	// ---------------------------------------------------------------------------------------------
	public function generateMigrationRow($colType,$colPrefix,$colName,$col,$data=null, $editConstFile = true){

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
		elseif($colType == 'file' || $colType == 'image'){
			$value = 'string('.$colName.',250)-> nullable();;';
		}
		elseif($colType == 'datetime' || $colType == 'timestamp'){
			$value = 'datetime('.$colName.') -> nullable();';
		}elseif($colType == 'time'){
			$value = 'time('.$colName.') -> nullable();';
		}elseif($colType == 'date'){
			$value = 'date('.$colName.') -> nullable();';
		} elseif($colType == 'bool'){
			$value = 'boolean('.$colName.') ->default(false);';
		}
		elseif(strpos($colType,'enum') !== false){
			$enumConsts = $this -> enumConstGenerator($data,$colPrefix,$col, $editConstFile);
			$value = 'enum('.$colName.',['.$enumConsts.']);';
		}

		if(isset($value)){
			return '$table->'.$value."\n";
		}

		return '';
	}


	// ---------------------------------------------------------------------------------------------
	public function enumConstGenerator($enums,$colPrefix,$col, $editConstFile){
		$data1= "";
		$enumConsts = [];
		foreach($enums as $enum){
			$enumName='ENUM_'.strtoupper($colPrefix).'_'.strtoupper($col).'_'.strtoupper($enum);
			$enumConsts[] = $enumName;
			$data1.="define('$enumName', '".strtolower($enum)."');\n";
		}
		$data1.="\n\n\n";

		if($editConstFile){
			$this->writeConsts($data1,'enum');

		}

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
    private function generateEnumsUtilsJsonFileData($utilsJsonFileData, $enumsArray, $modalName, $col){

        if($enumsArray != null){
            foreach($enumsArray as $enum){
                $utilsEnumData[$enum] = $enum;
            }

            list($singularEnumName, $pluralEnumName) = $this->createEnumNames($modalName, $col);

            $utilsJsonFileData[$pluralEnumName] = $utilsEnumData;

            if(strpos($col, "status") !== false){
                foreach($enumsArray as $enum){
                    $utilsEnumData[$enum] = "primary";
                }
                $utilsJsonFileData[$singularEnumName."Colors"] = $utilsEnumData;
            }
        }

        return $utilsJsonFileData;
    }


    private function createEnumNames($modalName, $col){
        $sOrEs = 's';
        if( in_array( substr($col, -1), ["s","z","ch","sh","x"] ) )
            $sOrEs = "es";

        $enumName = strtolower($modalName).ucfirst($col);

        return [$enumName, $enumName.$sOrEs];
    }


    // ---------------------------------------------------------------------------------------------
    private function generateAccessorsData($accessorsData, $enumsArray, $modalName, $col, $colName, $colType){
        $ccColName = ucfirst(\Str::camel($col));

        if(in_array($colType, ["date","datetime"])){
            $accessorsData .='
                public function get'.$ccColName.'Attribute($value){
                    $this->append('.$colName.'."_fa");
                    return $value;
                }
            '.PHP_EOL;

            $accessorsData .='
                public function get'.$ccColName.'FaAttribute(){
                    return UC($this->attributes['.$colName.'],U_MILADI_TO_HEJRI);
                }
            '.PHP_EOL;
        }
        elseif($enumsArray != null){
            list($singularEnumName, $pluralEnumName) = $this->createEnumNames($modalName, $col);

            $statusColorAppend = "";
            if(strpos($col, "status") !== false){
                $statusColorAppend = '$this->append('.$colName.'."_color");';
            }

            $accessorsData .='
                public function get'.$ccColName.'Attribute($value){
                    $this->append('.$colName.'."_text");
                    '.$statusColorAppend.'
                    return $value;
                }
            '.PHP_EOL;

            $accessorsData .='
                public function get'.$ccColName.'TextAttribute(){
                    return UC($this->attributes['.$colName.'], "'.$pluralEnumName.'");
                }
            '.PHP_EOL;

            if($statusColorAppend  != ""){
                $accessorsData .='
                    public function get'.$ccColName.'ColorAttribute(){
                        return UC($this->attributes['.$colName.'], "'.$singularEnumName.'Colors");
                    }
                '.PHP_EOL;
            }
        }

        return $accessorsData;
    }


    // ---------------------------------------------------------------------------------------------
	public function writeConsts($data, $tableOrEnum='table') {

		$constsPath=self::constsPath;
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
		$ExistMigrations=scandir(self::migrationsPath);
		Artisan::call("make:migration create_".$table."_table --create=".$table);
		$diff=array_diff(scandir(self::migrationsPath),$ExistMigrations);

		$migrationName = array_values($diff)[0];
		$migrationPath=self::migrationsPath.$migrationName;
		$migrationFileContent=file_get_contents($migrationPath);
		$migrationFileContent=str_replace("'".strtolower($table)."'",$tableName,$migrationFileContent);
		$migrationFileContent=str_replace('$table->id();',$migrateData,$migrationFileContent);
		file_put_contents($migrationPath,$migrationFileContent);

	}


    // ---------------------------------------------------------------------------------------------
	public function writeModel($modalName, $scopeData, $scopeHintData, $accessorsData) {
		Artisan::call("make:model $modalName");

        $scopeData .= $accessorsData;

		$modelPath=self::modelsPath.$modalName.'.php';
		$modelFileContent=file_get_contents($modelPath);
		$modelFileContent=str_replace('use HasFactory;',$scopeData,$modelFileContent);
		$modelFileContent=substr_replace($modelFileContent,$scopeHintData,125,0);
		$modelFileContent=substr_replace($modelFileContent,'use App\Models\ModelEnhanced;',29,0);

		$ModelPos=strpos($modelFileContent,'Model',strpos($modelFileContent,'extends'));
		$modelFileContent=substr_replace($modelFileContent,'ModelEnhanced',$ModelPos,5);

		file_put_contents($modelPath,$modelFileContent);
	}


    // ---------------------------------------------------------------------------------------------
	public function writeFactory($modalName, $factoryData) {
		Artisan::call("make:factory ".$modalName."Factory --model=".$modalName);
		$factoryPath=self::factoriesPath.$modalName.'Factory.php';
		$factoryFileContent=file_get_contents($factoryPath);
		$factoryFileContent=str_replace('//',$factoryData,$factoryFileContent);
		file_put_contents($factoryPath,$factoryFileContent);
	}


	// ---------------------------------------------------------------------------------------------
	public function writeController($withController, $modelName, $colNames, $controllerIndexFilterVariables, $controllerIndexFilterScopes) {
		if ($withController != null) {
			Artisan::call("make:controller $modelName" . "Controller -r");
			$controllerName = $modelName . "Controller";
			$this->fillControllerMethods($modelName, $colNames, $controllerIndexFilterVariables, $controllerIndexFilterScopes);
			$this->createRoute($modelName, $controllerName);
		}
	}


    // ---------------------------------------------------------------------------------------------
    public function fillControllerMethods($modelName,$cols, $controllerIndexFilterVariables, $controllerIndexFilterScopes){
        $controllerPath = self::controllersPath.$modelName.'Controller.php';
        $content = file_get_contents($controllerPath);

        $colsData = "";
        $idColConst = "id";
        foreach($cols as $index => $col){
            if($index ==  "id")
                $idColConst = $col["const"];
            if($index !=  "id"){
                if(in_array($col["type"], ["file","image","icon"]))
					$colsData .= '          $item['.$col["const"].'] = \Tools::uploadAndCompressImage(request()->file('.$col["const"].'), PATH_UPLOAD);'."\n";
				else
                	$colsData .= '          $item['.$col["const"].'] = request('.$col["const"].');'."\n";
            }
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
		'.$controllerIndexFilterVariables.'


		$builder = '.$modelName.'::id($id)->'.$controllerIndexFilterScopes.';
		$count = $builder->count();
		$items = $builder->orderByDesc('.$idColConst.')->page2($page, $rowsCount)->get();

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
            return generateResponse(RES_SUCCESS,array(\'item\' => $item));

        ';
        $content = substr_replace($content,$storeOp,$storeOperationPos,2);


        // ------------------------------------------------------------- update
        $updatePos = strpos($content,'update(');
        $updateOperationPos = strpos($content,'//',$updatePos);
        $updateOp = '
            $item = '.$modelName.'::findOrError($id);
'.$colsData.'
            $item -> save();
            return generateResponse(RES_SUCCESS,array(\'item\' => $item));
        ';
        $content = substr_replace($content,$updateOp,$updateOperationPos,2);


        // ------------------------------------------------------------- destroy
        $destroyPos = strpos($content,'destroy(');
        $destroyOperationPos = strpos($content,'//',$destroyPos);
        $destroyOp = '
            $item = '.$modelName.'::findOrError($id);
            $item -> delete();
            return generateResponse(RES_SUCCESS);
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
		$routePath=self::routesPath;
		$routeData='
	Route::resource("'.lcfirst($modelName).'","'.$controllerName.'");';
		file_put_contents($routePath,$routeData,FILE_APPEND);

	}


    // ---------------------------------------------------------------------------------------------
	public function writeDbStructureJson($data) {
        if(sizeof($data) > 0){

            if(file_exists(self::dbStructureJsonFilePath)){
                $jsonFileContent = json_decode(file_get_contents(self::dbStructureJsonFilePath), true);

                $jsonFileContent["tables"][] = $data;
            }else{
                $jsonFileContent["tables"][] = $data;
            }

            file_put_contents(self::dbStructureJsonFilePath, json_encode($jsonFileContent, JSON_PRETTY_PRINT), FILE_APPEND);
        }
	}


    // ---------------------------------------------------------------------------------------------
	public function writeUtilsJson($data) {
        if(sizeof($data) > 0){

            $jsonFileContent = $data;

            if(file_exists(self::utilsJsonFilePath)){
                $jsonFileContent = json_decode(file_get_contents(self::utilsJsonFilePath), true);

                $jsonFileContent = array_merge($jsonFileContent, $data);
            }


            file_put_contents(self::utilsJsonFilePath, json_encode($jsonFileContent, JSON_PRETTY_PRINT));
        }
	}


    // -------------------------------------------------------------------------------------------------------------

	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------
	// ----------------------------------------------------------------------------------------------------------------------------------------------



	public function columnAdd(){
		$table = request('table');
		$cols = request('cols');



		$constsPath=self::constsPath;
		$constsFileContent=file_get_contents($constsPath);

		$startColumnsPos=$this->getStartPosOfDefineColumns($table,$constsFileContent);

		$colPrefix = $this->getColumnPrefix($constsFileContent,$startColumnsPos);

		$model = Str::studly(Str::singular($table)); // todo: get $colPrefix and $model from user - we can suggest these value in index method to user

		$modelDirectory = 'App\Models\\' . $model;
		if(!class_exists($modelDirectory)) {
			dd('Model not found'); // TODO
		}



		list($newCols, $removedCols, $modifiedCols) = $this->getColumnsModifications($cols, $table);

		if(sizeof($removedCols) > 0)
			dd("NOT ALLOWED TO REMOVE COLS");


		$this->addNewColumn($newCols,$colPrefix, $table, $model,$constsFileContent,$startColumnsPos,$constsPath);

		$this->modifyColumn($modifiedCols,$table,$colPrefix,$model);



		dd('asd');

	}

	private function getStartPosOfDefineColumns($table,$constsFileContent) {
		$tableName="TBL_".strtoupper($table);
		$pos=strpos($constsFileContent, "define('".$tableName."', '".$table."')");
		$pos=strpos($constsFileContent, "\n",$pos); // position of end of line of define TBL

		return $pos;
	}

	private function getColumnPrefix($constsFileContent, $startColumnsPos) {

		$colSample = \substr($constsFileContent, $startColumnsPos+1, strpos($constsFileContent, "\n",$startColumnsPos+1) - $startColumnsPos); // =>  define('COL_BASKET_ID', 'id');

		// extracting the column name and make it upper case : id => ID
		$colSampleName = substr($colSample, strpos($colSample, ","));
		$colSampleName = \str_replace([',',"'",")","\n",";"],'',$colSampleName);
		$colSampleName = strtoupper($colSampleName);
		$colSampleName = trim($colSampleName);  // => ID

		// extracting something like this : COL_BANK_ID    and turn it to : COL_BANK_   as the prefix
		$colSamplePrefix = substr($colSample, strpos($colSample, "("),  strpos($colSample, ",") - strpos($colSample, "("));
		$colSamplePrefix = \str_replace(['(',"'","'",'COL_'],'',$colSamplePrefix);
		$colSamplePrefix = substr($colSamplePrefix,0, strpos($colSamplePrefix,'_'.$colSampleName));
		$colSamplePrefix = \str_replace('_','',$colSamplePrefix);

		return $colSamplePrefix;
	}














	// ==============================================================================================
	public function getColumnsModifications($cols, $table){
		$enteredColsAsArray =explode("\r\n",$cols);
		$selectedTableExistingColumns = DB::select("SHOW COLUMNS FROM ". $table);
		$newCols = [];
		$removedCols = [];
		$modifiedCols = [];


		foreach($enteredColsAsArray as $enteredCol){

			$enteredColName = explode("=",$enteredCol)[0];

			$enteredColExisits = false;

			foreach($selectedTableExistingColumns as $existingColumn){
				if($existingColumn -> Field == $enteredColName)
					$enteredColExisits = true;
			}

			if(!$enteredColExisits)
				$newCols[] = $enteredCol;
		}


		foreach($selectedTableExistingColumns as $existingColumn){

			$colStillExists = false;

			foreach($enteredColsAsArray as $enteredCol){

				$enteredColName = explode("=",$enteredCol)[0];

				if($existingColumn -> Field == $enteredColName){
					$colStillExists = true;


					$modifiedCol = $this->checkIfEnteredExsitingColDiffersFromTheOneInDatabase($existingColumn,$enteredCol);

					if(sizeof($modifiedCol) > 0)
						$modifiedCols[] = $modifiedCol;
				}
			}

			if(!$colStillExists)
				$removedCols[] = $existingColumn->Field;
		}

		return [$newCols, $removedCols, $modifiedCols];
	}



	// -----------------------------------------------------------------------------------------------------
	public function convertDatabaseColumnTypeToOurStandard($col){
		// Field is the name of column , Type is the type of column

		$colType = $col -> Type;

		if(\strpos($colType,"int(") !== false)
			$colType = 'integer';
		elseif(\strpos($colType,"varchar") !== false)
			$colType = 'string';
		elseif(\strpos($colType,"timestamp") !== false)
			$colType = 'datetime';
		elseif(\strpos($colType,"enum") !== false){

			$colType = \str_replace("enum","",$colType);
			$colType = \str_replace("(","",$colType);
			$colType = \str_replace(")","",$colType);
			$colType = \str_replace("'","",$colType);
			$enums = explode(",",$colType);

			$colType = 'enum:';
			foreach($enums as $index =>$enum){

				if($index+1 < sizeof($enums))
					$colType .= $enum.',';
				else
					$colType .= $enum;
			}
		}

		$col -> Type = $colType;

		return $col;
	}


	// -------------------------------------------------------------------------------------------------------
	// this function checks for modification in existing columns , not removed or new columns
	public function checkIfEnteredExsitingColDiffersFromTheOneInDatabase($existingColumn,$enteredCol){

		$modifiedCols = [];

		$enteredType = substr($enteredCol, strpos($enteredCol, "=") + 1);
		$enteredType = trim($enteredType);
		$enteredType = $enteredType=='int'?'integer':$enteredType;
		$enteredType = $enteredType=='str'?'string':$enteredType;

		$existingColumn = $this->convertDatabaseColumnTypeToOurStandard($existingColumn);
		$currentType = $existingColumn -> Type;
		$colName = $existingColumn -> Field;


		if(strpos($enteredType,"enum") === false){ // not enum

			if($currentType != $enteredType){
				$modifiedCols['is_enum'] = 0;
				$modifiedCols['name'] = $colName;
				$modifiedCols['old_type'] = $currentType;
				$modifiedCols['new_type'] = $enteredType;
				$modifiedCols['enums'] = null;
			}

		}
		elseif(strpos($enteredType,"enum") !== false){

			$currentType = \str_replace("enum","",$currentType);
			$currentType = \str_replace(":","",$currentType);

			$currentEnumsArray = explode(",",$currentType);

			$enteredType = \str_replace("enum","",$enteredType);
			$enteredType = \str_replace(":","",$enteredType);

			$enteredEnumsArray = explode(",",$enteredType);


			$modifiedCols['name'] = $colName;
			$modifiedCols['is_enum'] = 1;
			$modifiedCols['enums'] = $enteredEnumsArray;
			$modifiedCols['new_type'] = 'enum';
			$modifiedCols['old_enums'] = $currentEnumsArray;
			$modifiedCols['removed_enums'] = array_diff($currentEnumsArray, $enteredEnumsArray);
			$modifiedCols['added_enums'] = array_diff($enteredEnumsArray, $currentEnumsArray);


		}

		return $modifiedCols;
	}








	// ---------------------------------------------------------------------------------------
	public function colsDataCreation($cols,$colPrefix,$tableName,$modalName){

		if(!\is_array($cols))
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


				if(strpos($colType,"enum") !== false){
					$currentType = \str_replace("enum","",$colType);
					$currentType = \str_replace(":","",$currentType);

					$currentEnumsArray = explode(",",$currentType);

					$migrationColType = $this -> migrationColType('enum',$colPrefix, $colName,$col,$currentEnumsArray, true);
					$factoryColValue = $this -> factoryColValue('enum',$currentEnumsArray);

				}
				else{
					$migrationColType = $this -> migrationColType($colType,$colPrefix, $colName,$col);
					$factoryColValue = $this -> factoryColValue($colType);

				}


				$migrateData.='$table->'.$migrationColType."\n";

				$factoryData .= $colName.' => '.$factoryColValue.','."\n";
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






	// ----------------------------------------------------------------------------------------------------------------
	public function addNewColumn($newCols,$colSamplePrefix, $table, $model,$constsFileContent,$pos,$constsPath){
		// creating the new column constant : COL_BANK_NEW
		list($data,$tableName,$migrateData,$scopeData,$scopeHintData,$factoryData, $colNames) = $this->colsDataCreation($newCols,$colSamplePrefix, $table, $model);
		$data = trim($data);

		$constsFileContent=substr($constsFileContent,0,$pos+1).$data."\n".substr($constsFileContent,$pos+1);


		// finding migration file
		$migFile = DB::table('migrations')->where('migration','LIKE',"%$table%")->first();
		if($migFile == null)
			dd('ERROR : migration file not found in the database migration table');

		$migFilePath=self::migrationsPath.$migFile->migration.'.php';
		$migFileContent = file_get_contents($migFilePath);


		$tPos1 = strpos($migFileContent,'$table', strpos($migFileContent, '$table') + 1); // TODO : find last $table
		$tPos2 = strpos($migFileContent,"\n",$tPos1);
		$migFileContent=substr($migFileContent,0,$tPos2+1).$migrateData."\n".substr($migFileContent,$tPos2+1);



		// factory
		$factoryPath=self::factoriesPath.$model.'Factory.php';
		$factoryFileContent=file_get_contents($factoryPath);
		$facPos1 = strpos($factoryFileContent, "]");
		$factoryFileContent=substr($factoryFileContent,0,$facPos1-1).$factoryData."\n".substr($factoryFileContent,$facPos1-1);
		$factoryFileContent = trim($factoryFileContent);



		// TODO
		// model
		// if($scopeData != ""){
//		     $modelPath=self::modelsPath.$model.'.php';
		//     $modelFileContent=file_get_contents($modelPath);
		//     $modelFileContent=str_replace('use HasFactory;',$scopeData,$modelFileContent);
		//     $modelFileContent=substr($modelFileContent,0,125).$scopeHintData.substr($modelFileContent,125);
		//     $modelFileContent=substr($modelFileContent,0,29).'use App\Models\ModelEnhanced;'.substr($modelFileContent,29);
		//     $ModelPos=strpos($modelFileContent,'Model',strpos($modelFileContent,'extends'));
		//     $modelFileContent=substr_replace($modelFileContent,'ModelEnhanced',$ModelPos,5);

		// file_put_contents($modelPath,$modelFileContent);

		// }





		file_put_contents($constsPath,$constsFileContent);
		file_put_contents($migFilePath,$migFileContent);
		file_put_contents($factoryPath,$factoryFileContent);


//		   DB::statement('ALTER TABLE '.$table.' ADD new1 int');
	}




	// -----------------------------------------------------------------------------------------
	public function modifyColumn($modifiedCols,$table,$colSamplePrefix,$model){

		foreach($modifiedCols as $col){



			$colNameConstant  = 'COL_'.$colSamplePrefix.'_'.strtoupper($col['name']);

			// -----------------------  mofiding migration file
			$migFile = DB::table('migrations')->where('migration','LIKE',"%$table%")->first();
			$migFilePath=self::migrationsPath.$migFile->migration.'.php';
			$migFileContent = file_get_contents($migFilePath);


			$migrateData = $this -> generateMigrationRow($col['new_type'],$colSamplePrefix, $colNameConstant ,$col['name'],$col['enums'], false);

			$tPos1 = strpos($migFileContent,$colNameConstant);
			$tPos2 = strrpos($migFileContent,'$table',-strlen($migFileContent)+$tPos1-1); // search before
			$tPos3 = strpos($migFileContent,"\n",$tPos1);
			$migFileContent=substr($migFileContent,0,$tPos2).$migrateData."\n".substr($migFileContent,$tPos3+1);

			file_put_contents($migFilePath,$migFileContent);


			// ---------------------------------------------- factory file
			$factoryPath=self::factoriesPath.$model.'Factory.php';
			$factoryFileContent=file_get_contents($factoryPath);
			$facPos1 = strpos($factoryFileContent, $colNameConstant);
			$facPos2 = strpos($factoryFileContent,"\n",$facPos1);

			$factoryData = $this -> generateFactoryRow($col['new_type'],$colNameConstant,$col['enums']);

			$factoryFileContent=substr($factoryFileContent,0,$facPos1).$factoryData."\n".substr($factoryFileContent,$facPos2+1);
			$factoryFileContent = trim($factoryFileContent);

			file_put_contents($factoryPath,$factoryFileContent);


			if($col['is_enum'] == 0){
				$sql = 'ALTER TABLE '.$table.' MODIFY  COLUMN '.$col['name'].''.$col['new_type'];
			}
			else{
				$sql = 'ALTER TABLE '.$table.' MODIFY  COLUMN '.$col['name'].' enum(\''.implode("','",$col['enums']).'\')';


				$constsPath=self::constsPath;
				$constsFileContent=file_get_contents($constsPath);
				$pos=0;


				$a = [];


				foreach($col['old_enums'] as $oldEnum){
					$enumConst = "ENUM_". \strtoupper($colSamplePrefix)."_".strtoupper($col['name'])."_".strtoupper($oldEnum);
					$a[] = $enumConst;

					$pos=strpos($constsFileContent, $enumConst);
					$pos2=strrpos($constsFileContent, "\n", -strlen($constsFileContent)+$pos-1)+1; // search before
					$pos3=strpos($constsFileContent,"\n",$pos);

					$constsFileContent =substr($constsFileContent,0,$pos2).substr($constsFileContent,$pos3+1);
				}

				file_put_contents($constsPath,$constsFileContent);
				$enumConsts = $this -> enumConstGenerator($col['enums'],$colSamplePrefix,$col['name']);

			}

//			 DB::statement($sql);

			$sqlchangesData = "\n # const generator \n $sql \n";
			\file_put_contents(base_path().'/db_changes.sql',$sqlchangesData, FILE_APPEND);


		}
		}



		// ---------------------------------------------------------------------------------------

	public function casts(){
		$files = scandir(app_path(self::migrationsPath));
		$allcasts = '';
		$cols = [];

		foreach($files as $file){
			if($file != '.' && $file != '..' ){
				$m = file_get_contents(app_path(self::migrationsPath.$file));
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



