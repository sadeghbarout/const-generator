<template>
    <div class="row">
        <div class="col-md-6 mx-auto mt-5">


            <card-component title="  مشخصات #detail-card-name  ">
                <div class="col-12">
                    <!-- <img class="img-fluid rounded-circle mh-70" :src="item.image"> -->

                    #form-labels

                    <br>
                    <router-link  :to="'/#base-url/create/'+item.id" class="btn btn-warning btn-sm">ویرایش</router-link>
                    <button  class="btn btn-danger btn-sm float-left" @click="deleteItem()">حذف</button>
                </div>
            </card-component>


        </div>
    </div>
</template>
<script>
    export default {
        data(){
            return {
                item:{},
                #vue-data
            }
        },
        methods: {
            fetchData(){
                axios.get('/#base-url/'+this.$route.params.id)
                .then(response=>{
                    checkResponse(response.data,response=>{
                        this.item = response.item
                    },true)
                })
            },

            deleteItem(){
                confirm2('از حذف این آیتم اطمینان دارید؟','حذف',()=>{
                    axios.delete('/#base-url/'+this.$route.params.id)
                    .then(response=>{
                        checkResponse(response.data)
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
