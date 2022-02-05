@extends('builder::layout')
@section('content')
<div id="app">

    <div class="row p-2 d-flex">
        <div v-for="table in tables" class="col-md-5 mx-auto text-center p-0 d-flex card ">


            <div class="card-header bg-dark text-main">
                <div class="row ">
                    <div class="col-6 ">
                        Model : <input v-model="table.model_name" class="border  rounded ">
                    </div>
                    <div class="col-6 ">
                        Table  : <input v-model="table.name" class="border  rounded ">
                    </div>
                </div>
            </div>

            <div class="card-body bg-dark text-light">
                <div  class="row  p-2">
                    <div v-for="col in table.cols" class="col-3  p-2">
                        <div class="rounded border p-1">
                            <b class="d-block text-main">@{{col.name}}</b>
                            <label class="d-flex align-items-center justify-content-center" style="font-size: 12px">
                                in index
                                <input type="checkbox" class="m-1" @click="toggleInIndex(col)" :checked="col.in_index_table == 1 ? true : false">
                            </label>
                            <input v-model="col.html_type" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-footer bg-dark text-light">
                <div class="d-flex align-items-center justify-content-between flex-row w-100">
                    <div class="d-flex align-items-center">
                        <label class="d-flex align-items-center justify-content-center" style="font-size: 12px">
                            index page
                            <input type="checkbox" class="m-1" @click="toggleCreatePage(table, 'index')" vlaue="1" checked="checked">
                        </label>
                        <label class="d-flex align-items-center justify-content-center" style="font-size: 12px">
                            show page
                            <input type="checkbox" class="m-1" @click="toggleCreatePage(table, 'show')" checked="checked">
                        </label>
                        <label class="d-flex align-items-center justify-content-center" style="font-size: 12px">
                            create page
                            <input type="checkbox" class="m-1" @click="toggleCreatePage(table, 'create')" checked="checked">
                        </label>
                    </div>
                    <button @click="createVue(table)" class="btn bg-main">Create Vue Files</button>
                </div>
            </div>


        </div>
    </div>

</div>


@push('scripts')
<script>
    Vue.createApp({
        data(){
            return{
                tables:{!! $tables !!}
            }
        },
        methods: {
            createVue(table){
                axios.post('/builder/vue/create',{
                    table:JSON.stringify(table)
                })
                .then(res=>{
                    checkResponse(res.data);
                })
            },


            toggleInIndex(col){
                if(col.in_index_table == 1)
                    col.in_index_table = 0;
                else
                    col.in_index_table = 1;
            },


            toggleCreatePage(table, page){
                if(table[page] == 1)
                    table[page] = 0;
                else
                    table[page] = 1;
            },

        },
        mounted(){
        },
    }).mount('#app')

</script>
@endpush

@endsection

