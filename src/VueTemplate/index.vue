<template>
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="flex-center-between">
                <h3>  #page-name </h3>
                <router-link  :to="'/#base-url/create'" class="btn btn-outline-success "> جدید</router-link>
            </div>
            <br>
            <div class="row d-flex align-items-center">
                <div class="col-lg-10">
                    <form @submit.prevent="fetchData(1)">
                        <div class="row text-center d-flex align-items-center">
                            #filters
                            <div class="col-sm-3">
                                <form-page-rows v-model="pageRows" @input="fetchData(1)"></form-page-rows>
                            </div>
                            <div class="col-sm-2">
                                <button class="btn btn-outline-primary w-100 mt-4">جستجو</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-lg-2">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="bg-primary">
                        <tr>
                            #table-col-names
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(item, index) in items" :key="item.id">
                            #table-body-col
                            <td>
                                <button @click="deleteItem(item, index)" class="btn btn-danger btn-sm">حذف</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <pagination :pages="pageCount"  v-model="page" @pageChanged="fetchData()"></pagination>
            </div>


        </div>
    </div>
</template>
<script>
    export default {
        data(){
            return {
                items:[],
                pageCount:1,
                page:1,
                pageRows:10,
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
        mounted(){
            this.fetchData()

            #get-enums
        },
    }
</script>
