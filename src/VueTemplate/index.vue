<template>
    <div id="data-list-view" class="data-list-view-header">

        <!-- filters -->
        <form @submit.prevent="page=1;fetchData()">
            <card-component>
                #filters
                <div class='col-lg-3 col-md-6 mt-2'>
                    <button class="btn btn-outline-primary w-100">جستجو</button>
                </div>
            </card-component>
        </form>
        <!-- / -->


        <!-- title and action -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="content-header-title float-left mb-0"> #page-name  </h2>
            </div>
            <div class="d-flex align-items-center" style="gap:8px;">
<!--                <excel-export-button @on-export="fetchData" :loading="isFetchingData"/>-->

                <custom-column-dialog type="#base-url" v-model="selectedColumnsData" :selectedColumnIds="selectedColumnIds"/>

                <router-link :to="'/#base-url/create'" class="btn btn-primary">
                    <span>
                        <i class="fas fa-plus"></i> جدید
                    </span>
                </router-link>
                <form-page-rows/>
            </div>
        </div>
        <!-- / -->

        <!-- table -->
        <div class="table-responsive table-list">
            <table class="table data-list-view px-0">
                <thead>
                    <tr>
                        <th>
                            <check-td :header="true"/>
                        </th>

                        <custom-column-th :selectedColumnsData="selectedColumnsData" :filters="filtersItems" :sortOptions="sort"/>

                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="item.id" :id="'row'+item.id">
                        <td>
                            <check-td :id="item.id"/>
                        </td>

                        <custom-column-td :selectedColumnsData="selectedColumnsData" :to='"/#base-url/"+item.id' :item="item" ></custom-column-td>

                        <td>
                            <button @click="deleteItem(item, index)" class="btn btn-danger btn-sm">حذف</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-if="items.length == 0" class="alert alert-warning text-center w-100 my-2">آیتمی یافت نشد</div>
            <pagination :pages="pageCount" v-model="page" @pageChanged="fetchData()"></pagination>
        </div>

        <!-- / -->
    </div>
</template>
<script>
    export default {
        mixins:[window.urlMixin],
        data(){
            return {
                items:[],
                pageCount:1,
                page:1,
                pageRows:10,
                selectedIds: [],

                selectedColumnIds: [],
                selectedColumnsData: [],
                filtersItems: [],
                sort: {},
                isFetchingData: false,

                #vue-data
            }
        },
        methods: {
            fetchData(excelExport = 0) {
                this.isFetchingData = true

                axios.get('/#base-url',{
                    params:{
                        #axios-get-params
                        page:this.page,
                        rows_count:this.pageRows,
                        sort: this.sort,
                        filters: this.filtersItems,
                        export: this.excelExport,
                    },
                })
                .then(response=>{
                    checkResponse(response.data,response=>{
                        this.isFetchingData = false

                        if(excelExport===1){
                            window.location.replace(response.link)
                            return;
                        }

                        this.items = response.items
                        this.pageCount = response.page_count;
                        this.selectedColumnIds = response.selected_columns;
                        this.selectedColumnsData=Tools.getSelectedColumnsData('#base-url',this.selectedColumnIds);
                    },true)
                })
                .catch(() => {
                    this.isFetchingData = false
                })
            },


            deleteItem(item, index){
                confirm2('آیا از حذف این آیتم اطمینان دارید؟','حذف',()=>{
                    axios.delete('/#base-url/'+item.id)
                    .then(response=>{
                        checkResponse(response.data, res=>{
                            this.items.splice(index, 1)
                        })
                    })
                })
            },

        },
        activated(){
            this.fetchData()

            #get-enums
        },
    }
</script>
