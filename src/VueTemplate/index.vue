<template>
    <div id="data-list-view" class="data-list-view-header">

        <!-- filters -->
        <form @submit.prevent="page=1;fetchData()">
            <card-component>
                #filters
                <div class="col-sm-3">
                    <form-inputs type="submit" val="جستجو" customClass="btn btn-outline-primary mt-2"></form-inputs>
                </div>
            </card-component>
        </form>
        <!-- / -->


        <!-- title and action -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="content-header-title float-left mb-0"> #page-name  </h2>
                <router-link :to="'/#base-url/create'" class="btn btn-primary">
                    <span>
                        <i class="fas fa-plus"></i> جدید
                    </span>
                </router-link>
            </div>
            <form-page-rows v-model="pageRows" @input="fetchData(1)"></form-page-rows>
        </div>
        <!-- / -->

        <!-- table -->
        <div class="table-responsive" v-if="items.length > 0">
            <table class="table data-list-view dataTable">
                <thead>
                    <tr>
                        <th>
                            <check-td :header="true"></check-td>
                        </th>
                        #table-col-names
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, index) in items" :key="item.id" :id="'row'+item.id">
                        <td>
                            <check-td :id="item.id"></check-td>
                        </td>
                        #table-body-col
                        <td>
                            <button @click="deleteItem(item, index)" class="btn btn-danger btn-sm">حذف</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <pagination :pages="pageCount" v-model="page" @pageChanged="fetchData()"></pagination>
        </div>

        <div v-if="items.length == 0" class="alert alert-primary text-center w-100 mt-2">آیتمی یافت نشد</div>
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
                sort: '',
                sortType: 'desc',
                #vue-data
            }
        },
        methods: {
            fetchData(){
                axios.get('/#base-url',{
                    params:{
                        #axios-get-params
                        page:this.page,
                        rows_count:this.pageRows,
                        sort: this.sort,
                        sort_type: this.sortType,
                    },
                })
                .then(response=>{
                    checkResponse(response.data,response=>{
                        this.items = response.items
                        this.pageCount = response.page_count;
                    },true)
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
