@extends('builder::layout')
@section('content')



        <div class="row" id="app" style="width:90%; margin:auto">
            <div class="col-md-7">
                <form @submit.prevent="submitForm()">

                    <div class="row d-flex align-items-center">
                        <div class="col-md-6 form-group">
                            <label >Model name:</label>
                            <label style="color: #a4a4a4;font-size: 12px">(PascalCase/camelCase)(individual)</label>
                            <input class="form-control"  v-model="modalName" name="modalName" placeholder="OrderBasket" >
                        </div>


                        <div class="col-md-6 form-group">
                            <label >Table name:</label>
                            <label style="color: #a4a4a4;font-size: 12px">(snake_case)(sum)</label>
                            <input class="form-control" type="text" v-model="table" name="table" placeholder="order_baskets" >
                        </div>


                        <div class="col-md-6 form-group">
                            <label >Columns prefix:</label>
                            <label style="color: #a4a4a4;font-size: 12px">(snake_case)(individual)</label>
                            <input class="form-control" type="text" v-model="colPrefix" name="colPrefix" placeholder="order_basket" >
                        </div>


                        <div class="col-md-6 form-group d-flex align-items-center">
                            <label>With controller</label>
                            <label style="color: #a4a4a4;font-size: 12px">(whether to create controller or not)</label>
                            <input type="checkbox" v-model="withController" name="withController" checked style="width:25px; height:25px; margin:5px">
                        </div>

                        <div class="col-md-12 form-group">
                            <label for="comment">Columns:</label>
                            <textarea class="form-control" v-model="cols" name="cols" cols="60" rows="20" placeholder="id*
name*=str
signup_step=enum:validation,password"></textarea>
                        </div>
                    </div>


                    <div class="d-flex align-items-center justify-content-center">
                        <input type="submit" class="btn bg-main">
                    </div>
                </form>
            </div>



            <div class="col-md-5">
                <div class="alert bg-main position-fixed">
                    <p>1- id column will be primary automatically</p>
                    <p>2- * means scope in Model file (ex : name* will generate scopeName function)</p>
                    <p>3- general structure is : colName=type:extras</p>
                    <p>4- types are: text, integer (or int), tinyint, bigint, double, datetime, time, date, bool, enum , string(or str) ,password,phone,email,username</p>
                    <p>5- enum examples: </p>
                    <p>type=enum:type1,type2,type3</p>
                </div>
            </div>
        </div>



        @push('scripts')
            <script>
                Vue.createApp({
                    data(){
                        return{
                            modalName:'',
                            table:'',
                            colPrefix:'',
                            withController:true,
                            cols:'',
                        }
                    },
                    methods: {

                        submitForm(){
                            axios.post('/builder/const/add',{
                                modalName:this.modalName,
                                table:this.table,
                                colPrefix:this.colPrefix,
                                withController:this.withController,
                                cols:this.cols,
                            })
                            .then(res=>{
                                checkResponse(res.data, ()=>{
                                    window.location.reload()
                                });
                            })
                        },

                    },
                    mounted(){
                    },
                }).mount('#app')

            </script>
@endpush


@endsection





