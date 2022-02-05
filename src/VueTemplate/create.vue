<template>
    <div class="row">
        <div class="col-md-5 mx-auto mt-3">
            <card-component :title=" !id ? '  ثبت رکورد جدید ' : 'ویرایش '  " >
                <div class="col-12">
                    <form @submit.prevent="formSubmission()">

                        #form-inputs

                        <br>
                        <button class="btn btn-warning w-100 mx-auto">ثبت</button>
                    </form>
                </div>
            </card-component>

        </div>
    </div>
</template>
<script>
    export default {
        data(){
            return {
                item : {
                    #vue-data
                },
                id : '',
            }
        },
        methods: {

            formSubmission(){
                var formData = new FormData();
                #form-data



                if(this.id){
                    formData.append('_method', "patch");

                    axios.post('/#base-url/'+this.item.id,formData,{
                        headers: {'Content-Type': 'multipart/form-data'}
                    })
                    .then(response=>{
                        checkResponse(response.data);
                    })
                }
                else{
                    axios.post('/#base-url',formData,{
                        headers: {'Content-Type': 'multipart/form-data'}
                    })
                    .then(response=>{
                        checkResponse(response.data);
                    })
                }
            },


            fetchData(){
                axios.get('/#base-url/'+this.id)
                .then(res=>{
                    checkResponse(res.data,res=>{
                        this.item = res.item
                    },true)
                })
            },
        },
        mounted(){
            this.id = this.$route.params.id

            if(this.id !== '')
                this.fetchData();

            #get-enums
        },
    }
</script>
