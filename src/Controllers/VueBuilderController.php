<?php
namespace Colbeh\Consts\Controllers;

use Colbeh\Consts\Helper;
use Illuminate\Support\Facades\DB;

class VueBuilderController{


    public function index(){

//        $dbStructureFilePath = resource_path('files/database-structure.json');
//
//        if(file_exists($dbStructureFilePath)){
//            $data = json_decode( file_get_contents(resource_path('files/database-structure.json')), true );
//            $tables = json_encode($data["tables"]);
//        }
//        else
//            $tables = json_encode([]);


        $tables = $this->getTables();

        return view('builder::vueBuilderIndex', compact('tables'));
    }

    private function getTables() {
        $dbName = config('database.connections.mysql.database');
        $tablesRaw = DB::select('SHOW TABLES');

        $tables = [];
        $tablesIn = 'Tables_in_'.$dbName;
        foreach($tablesRaw as $table){

            $tableName = $table -> $tablesIn;

            if(in_array($tableName,["migrations","personal_access_tokens"]))
                continue;


            $tableColumns = DB::select("SHOW COLUMNS FROM ". $tableName);

            $tables[$tableName]['name'] = $tableName;
            $tables[$tableName]['model_name'] = \Str::studly(\str::singular($tableName));
            $tables[$tableName]['index'] = 1;
            $tables[$tableName]['show'] = 1;
            $tables[$tableName]['create'] = 1;

//            dd($tableColumns);

            $colsArray = [];
            foreach($tableColumns as &$tc){
                $colsArray[]=[
                    "name"=> $tc->Field,
                    "db_type"=> $tc->Type,
                    "html_type"=> $this->colHTMLType($tc)[0],
                    "in_index_table"=> $this->colHTMLType($tc)[1],
                ];
            }

            $tables[$tableName]['cols'] = $colsArray;
        }

        $tables = json_encode($tables);

        return $tables;
    }


    private function colHTMLType($col){
        // Field is the name of column , Type is the type of column

        $colType = $col -> Type;
        $colName = $col -> Field;

        $colHTMLType = "";
        $inIndex = 0;

        $possibleFileNames = ["image","icon","file","document","profile_image","logo"];


        if(\strpos($colType,"int(") !== false || \strpos($colType,"bigint(") !== false){
            $colHTMLType = 'number';

            if($colName == "id"){
                $inIndex = 1;
            }
        }
        elseif(\strpos($colType,"varchar") !== false){
            $colHTMLType = 'text';

            if(in_array($colName,$possibleFileNames))
                $colHTMLType = 'file';
            elseif(in_array($colName,["password"]))
                $colHTMLType = 'password';
            else{
                $inIndex = 1;
                $colHTMLType = 'text';
            }

        }
        elseif(\strpos($colType,"timestamp") !== false || \strpos($colType,"date") !== false  || \strpos($colType,"datetime") !== false){
            $colHTMLType = 'date';
        }
        elseif(\strpos($colType,"enum") !== false){
            $colHTMLType = 'select';
            $inIndex = 1;
        }
        elseif(\strpos($colType,"tinyint") !== false){
            $colHTMLType = 'checkbox'; // TODO
        }
        elseif(\strpos($colType,"text") !== false){
            if(in_array($colName,$possibleFileNames))
                $colHTMLType = 'file';
            else
                $colHTMLType = 'textarea';
        }

        return [$colHTMLType, $inIndex];
    }



    // -----------------------------------------------------------------------------------------------------------------------------------------
    public function vueCreate(){
        $table = json_decode(request('table'), true);

        // $getBasePath = resource_path('js/components/template')."/";
        $getBasePath = __DIR__."/../VueTemplate/";
        $putBasePath = resource_path('js/components')."/";
        $routeFilePath = resource_path('js/routes.js');


        $folderName = \Str::camel($table["model_name"]);
        if(!file_exists($putBasePath.$folderName ))
            \mkdir($putBasePath.$folderName);

		$this->createListColumns($table);

        if($table["index"] == 1)
            $this->createIndexPage($table, $getBasePath, $putBasePath);

        if($table["show"] == 1)
            $this->createShowPage($table, $getBasePath, $putBasePath);

        if($table["create"] == 1)
            $this->createCreatePage($table, $getBasePath, $putBasePath);


        $this->createRoutes($table, $routeFilePath);

        return Helper::sucBack('Vue files created');
    }


    // =========================================================================================================================
	public function createListColumns($table) {
		$listColumn = [];

		foreach ($table['cols'] as $index => $col){
			$listColumn [] = [
				"id" => $col['name'],
                "label" => $col['name'],
                "val" => $col['name'],
                "def" => $col['name'] === 'id' || $col['in_index_table'] === 1 ? 1 : 0,
				"sortable" => 1,
				"filterType" => "text",
			];

			if($col['html_type'] === "date"){
				$listColumn[$index]["filterType"] = 'date';
			}
		}

		$listColumnsPath = resource_path('files/list-columns.json');

		$keyName = strtolower($table['model_name']);
		$data = json_decode(file_get_contents($listColumnsPath), true);
		$data[$keyName]['list'] = $listColumn;

		file_put_contents($listColumnsPath, json_encode($data, JSON_PRETTY_PRINT));
	}

    // =========================================================================================================================
    private function createIndexPage($table, $getBasePath, $putBasePath){

        #page-name
        #base-url
        #filters
        #table-col-names
        #table-body-col
        #vue-data
        #axios-get-params
        #get-enums

        $indexTemplate = file_get_contents( $getBasePath."index.vue"  );

        $indexTemplate = str_replace("#page-name", $table["name"], $indexTemplate);

        $indexTemplate = str_replace("#base-url", strtolower($table["model_name"]), $indexTemplate);


        $indexTemplate = $this->createGetEnums($table, $indexTemplate);

        $indexTemplate = $this->createFilters($table, $indexTemplate);

        $indexTemplate = $this->createTableColNames($table, $indexTemplate);

        $indexTemplate = $this->createTableBodyCols($table, $indexTemplate);

        $indexTemplate = $this->createVueData($table, $indexTemplate);

        $indexTemplate = $this->createAxiosGetParams($table, $indexTemplate);

        file_put_contents($putBasePath.$table["model_name"]."/index.vue", $indexTemplate);
    }


    // =========================================================================================================================
    private function createGetEnums($table, $template, $templateNmae="index"){
        $data = "";

        foreach($table["cols"] as $col){
            if($templateNmae == "index" && $col["in_index_table"] != 1)
                continue;

            if($col["html_type"] == "select"){

                $colName = \Str::camel($col['name']);
                list($singularEnumName, $pluralEnumName) = $this->createEnumNames($table["model_name"], $colName);

                $enumName = $pluralEnumName;
                $vueVarName  = $this->getPural($colName);

                $withAll = $templateNmae == "index"? "true" : "false";

                $html ="this.$vueVarName = Tools.utils('$enumName', $withAll)".PHP_EOL;

                $data .= $html;
            }

        }

        $template = str_replace("#get-enums", $data, $template);

        return $template;
    }


    private function createEnumNames($modalName, $col){
        $enumName = strtolower($modalName).ucfirst($col);

        return [$enumName, $this->getPural($enumName)];
    }

    private function getPural($name) {
        $sOrEs = 's';
        if( in_array( substr($name, -1), ["s","z","x"] ) )
            $sOrEs = "es";
        if( in_array( substr($name, -2), ["ch","sh"] ) )
            $sOrEs = "es";

        return $name.$sOrEs;
    }


    // =========================================================================================================================
    private function createFilters($table, $template){
        $filters = "";

        foreach($table["cols"] as $col){
            if($col["in_index_table"] == 1){

                $ccColName = \Str::camel($col['name']);

                if($col["html_type"] == "select"){
                    $filterHtml ="
                        <div class='col-lg-3 col-md-6'>
                            <form-select title='".$col['name']."' v-model='".$ccColName."' :options='".$this->getPural($ccColName)."' ></form-select>
                        </div>".PHP_EOL;
                }
                else {
                    $filterHtml ="
                        <div class='col-lg-3 col-md-6'>
                            <form-inputs title='".$ccColName."' v-model='".$ccColName."' ></form-inputs>
                        </div>".PHP_EOL;
                }


                $filters .= $filterHtml;
            }
        }

        $template = str_replace("#filters", $filters, $template);

        return $template;
    }


    // =========================================================================================================================
    private function createTableColNames($table, $template){
        $data = "";

        foreach($table["cols"] as $col){
            if($col["in_index_table"] == 1){
                $html ="<th>".$col["name"]."</th>".PHP_EOL;

                $data .= $html;
            }
        }

        $template = str_replace("#table-col-names", $data, $template);

        return $template;
    }


    // =========================================================================================================================
    private function createTableBodyCols($table, $template){
        $data = "";

        foreach($table["cols"] as $col){
            if($col["in_index_table"] == 1){
                $link = "/".strtolower($table['model_name'])."/";

                if(strpos($col["db_type"], "enum") !== false)
                    $html ="<td><router-link :to='\"".$link."\"+item.id'>{{item.".$col["name"]."_text}}</router-link></td>".PHP_EOL;
                else
                    $html ="<td><router-link :to='\"".$link."\"+item.id'>{{item.".$col["name"]."}}</router-link></td>".PHP_EOL;

                $data .= $html;
            }
        }

        $template = str_replace("#table-body-col", $data, $template);

        return $template;
    }


    // =========================================================================================================================
    private function createVueData($table, $template, $templateNmae="index"){
        $data = "";

        foreach($table["cols"] as $col){
            if($templateNmae == "index" && $col["in_index_table"] != 1)
                continue;

            if(in_array($col['name'], ["created_at","updated_at"]))
                continue;

            $ccColName = \Str::camel($col['name']);

            if($col["html_type"] == "select"){
                $html = $ccColName ." : '', ".PHP_EOL;
                $html .= $this->getPural($ccColName)." : [], ".PHP_EOL;
            } else {

                if($templateNmae == "show")
                    continue;

                $html = $ccColName ." : '', ".PHP_EOL;
            }

            $data .= $html;
        }

        $template = str_replace("#vue-data", $data, $template);

        return $template;
    }


    // =========================================================================================================================
    private function createAxiosGetParams($table, $template){
        $data = "";

        foreach($table["cols"] as $col){

            $ccColName = \Str::camel($col['name']);

            if($col["in_index_table"] == 1){
                $html = strtolower($col["name"]) ." : this.".$ccColName.",".PHP_EOL;

                $data .= $html;
            }
        }

        $template = str_replace("#axios-get-params", $data, $template);

        return $template;
    }


    // =========================================================================================================================
    // =========================================================================================================================
    // =========================================================================================================================

    private function createShowPage($table, $getBasePath, $putBasePath){
        #detail-card-name
        #base-url
        #get-enums
        #form-labels


        $template = file_get_contents( $getBasePath."show.vue"  );

        $template = str_replace("#detail-card-name", $table["model_name"], $template);

        $template = str_replace("#base-url", strtolower($table["model_name"]), $template);


        $template = $this->createGetEnums($table, $template, "show");

        $template = $this->createVueData($table, $template, "show");

        $template = $this->createShowFormLabels($table, $template);


        file_put_contents($putBasePath.$table["model_name"]."/show.vue", $template);
    }


    // =========================================================================================================================
    private function createShowFormLabels($table, $template){
        $data = "";

        $possibleImages = ["image","icon","profile","profile_image","logo"];
        $colsNotToDisplay = ["password"];

        foreach($table["cols"] as $col){

            $colName = $col['name'];
            $ccColName = \Str::camel($colName);

            if(in_array(strtolower($colName),$colsNotToDisplay))
                continue;

            if($col["html_type"] == "file"){
                if(in_array($colName, $possibleImages))
                    $html ="<img class='img-fluid' :src='item.$colName'  >".PHP_EOL;
                else
                    $html ="<form-label title='$colName' :val='item.$colName' ></form-label>".PHP_EOL;
            }
            elseif($col["html_type"] == "date")
                $html ="<form-label title='$colName' :val='item.".$colName."_fa' ></form-label>".PHP_EOL;
            else{
                if(strpos($col["db_type"], "enum") !== false)
                    $html ="<form-label title='$colName' :val='item.".$colName."_text' ></form-label>".PHP_EOL;
                else
                    $html ="<form-label title='$colName' :val='item.$colName' ></form-label>".PHP_EOL;
            }

            $data .= $html;
        }

        $template = str_replace("#form-labels", $data, $template);

        return $template;
    }



    // =========================================================================================================================
    // =========================================================================================================================
    // =========================================================================================================================

    private function createCreatePage($table, $getBasePath, $putBasePath){

        #base-url
        #item-cols
        #form-inputs
        #form-data


        $template = file_get_contents( $getBasePath."create.vue"  );

        $template = str_replace("#base-url", strtolower($table["model_name"]), $template);

        $template = $this->createVueData($table, $template, "create");

        $template = $this->createGetEnums($table, $template, "create");

        $template = $this->createCreateFormInputs($table, $template);

        $template = $this->createCreateFormData($table, $template);

        file_put_contents($putBasePath.$table["model_name"]."/create.vue", $template);
    }


    private function createCreateFormInputs($table, $template){
        $data = "";

        foreach($table["cols"] as $col){

            $colName = $col['name'];
            $ccColName = \Str::camel($colName);
            $colHtmlType = $col["html_type"];

            if(in_array($colName, ["id","created_at","updated_at"]))
                continue;

            if($colHtmlType == "select"){
                $html ="<form-select title='$colName' v-model='item.$ccColName' :options='".$ccColName."s' ></form-select>".PHP_EOL;
            }
            else if($colHtmlType == "file"){
                $html ="<form-uploader title='$colName' v-model='item.$ccColName'  ></form-uploader>".PHP_EOL;
            }
            else if($colHtmlType == "date"){
                $html ="<form-date title='$colName' v-model='item.$ccColName'  ></form-date>".PHP_EOL;
            }
            else if($colHtmlType == "textarea"){
                $html ="<form-textarea title='$colName' v-model='item.$ccColName'  ></form-textarea>".PHP_EOL;
            }
            else {
                $html ="<form-inputs title='".$ccColName."' v-model='item.".$ccColName."' type='$colHtmlType' ></form-inputs>".PHP_EOL;
            }

            $data .= $html;
        }

        $template = str_replace("#form-inputs", $data, $template);

        return $template;
    }


    private function createCreateFormData($table, $template){
        $data = "";

        foreach($table["cols"] as $col){

            $colName = $col['name'];
            $ccColName = \Str::camel($colName);
            $colHtmlType = $col["html_type"];

            if(in_array($colName, ["id","created_at","updated_at"]))
                continue;

            $html ="formData.append('$colName', this.item.$colName);".PHP_EOL;

            $data .= $html;
        }

        $template = str_replace("#form-data", $data, $template);

        return $template;
    }


    // =================================================================================================================
    private function createRoutes($table, $routeFilePath){

        $content = file_get_contents($routeFilePath);

        $routesPos = strpos($content, "var routes");

        $imports = "";
        $routes = "";

        $folderName = \Str::camel($table["model_name"]);

        if($table["index"] == 1){
            $componentName = $folderName."Index";
            $imports  .= "import $componentName from './components/$folderName/index.vue';".PHP_EOL;
            $routes  .= " {path: '/$folderName',component: $componentName,meta:{title: '$folderName'}},".PHP_EOL;
        }
        if($table["create"] == 1){
            $componentName = $folderName."Create";
            $imports  .= "import $componentName from './components/$folderName/create.vue';".PHP_EOL;
            $routes  .= " {path: '/$folderName/create/:id?',component: $componentName,meta:{title: '$folderName'}},".PHP_EOL;
        }
        if($table["show"] == 1){
            $componentName = $folderName."Show";
            $imports  .= "import $componentName from './components/$folderName/show.vue';".PHP_EOL;
            $routes  .= " {path: '/$folderName/:id',component: $componentName,meta:{title: '$folderName'}},".PHP_EOL;
        }


        $imports = PHP_EOL.$imports;
        $routes = PHP_EOL.$routes;

        $content = substr_replace($content,$imports,$routesPos-1, 0);
        $routesPos = strpos($content, "var routes");
        $content = substr_replace($content,$routes,$routesPos+15, 0);

        file_put_contents($routeFilePath, $content);
    }


}
