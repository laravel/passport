<style scoped>
    .action-link {
        cursor: pointer;
    }

    .m-b-none {
        margin-bottom: 0;
    }

    .redirect-urls > .redirect-url {
        margin-bottom: 5px;
    }
    .redirect-urls > .redirect-url .input-group {
        width: 100%;
    }

    .redirect-urls > .redirect-url .input-group .form-control:first-child {
        border-radius: 4px;
    }
    .redirect-urls > .redirect-url ~ .redirect-url .input-group .form-control:first-child {
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }
</style>

<template>
    <div>
        <div class="panel panel-default">
            <div class="panel-heading">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span>
                        OAuth Clients
                    </span>

                    <a class="action-link" @click="showCreateClientForm">
                        Create New Client
                    </a>
                </div>
            </div>

            <div class="panel-body">
                <!-- Current Clients -->
                <p class="m-b-none" v-if="clients.length === 0">
                    You have not created any OAuth clients.
                </p>

                <table class="table table-borderless m-b-none" v-if="clients.length > 0">
                    <thead>
                        <tr>
                            <th>Client ID</th>
                            <th>Name</th>
                            <th>Secret</th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="client in clients">
                            <!-- ID -->
                            <td style="vertical-align: middle;">
                                {{ client.id }}
                            </td>

                            <!-- Name -->
                            <td style="vertical-align: middle;">
                                {{ client.name }}
                            </td>

                            <!-- Secret -->
                            <td style="vertical-align: middle;">
                                <code>{{ client.secret }}</code>
                            </td>

                            <!-- Edit Button -->
                            <td style="vertical-align: middle;">
                                <a class="action-link" @click="edit(client)">
                                    Edit
                                </a>
                            </td>

                            <!-- Delete Button -->
                            <td style="vertical-align: middle;">
                                <a class="action-link text-danger" @click="destroy(client)">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Client Modal -->
        <div class="modal fade" id="modal-create-client" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                        <h4 class="modal-title">
                            Create Client
                        </h4>
                    </div>

                    <div class="modal-body">
                        <!-- Form Errors -->
                        <div class="alert alert-danger" v-if="createForm.errors.message">
                            <p><strong>Whoops!</strong> Something went wrong!</p>
                            <br>
                            {{ createForm.errors.message }}
                        </div>

                        <!-- Create Client Form -->
                        <form class="form-horizontal" role="form">
                            <!-- Name -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">Name</label>

                                <div class="col-md-7">
                                    <input id="create-client-name" type="text" class="form-control"
                                                                @keyup.enter="store" v-model="createForm.fields.name">

                                    <div class="alert alert-danger" v-if="createForm.errors.errors && createForm.errors.errors.name">
                                        <ul>
                                            <li v-for="error in createForm.errors.errors.name">
                                                {{ error }}
                                            </li>
                                        </ul>
                                    </div>

                                    <span class="help-block">
                                        Something your users will recognize and trust.
                                    </span>
                                </div>
                            </div>

                            <!-- Redirect URLs -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">Redirect URL(s)</label>

                                <div class="col-md-7 redirect-urls">
                                    <div class="redirect-url" v-for="(url, index) in createForm.fields.redirect">
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                            @keyup.enter="store" v-model="createForm.fields.redirect[index]"/>

                                            <div class="input-group-btn" v-if="index > 0">
                                                <button class="btn btn-danger" 
                                                            @click.stop.prevent="removeRedirectUrl(createForm, index)">X</button>
                                            </div>
                                        </div>

                                        <div class="alert alert-danger" v-if="createForm.errors.errors && createForm.errors.errors['redirect.'+index]">
                                            <ul>
                                                <li v-for="error in createForm.errors.errors['redirect.'+index]">
                                                    {{ error }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <span class="help-block">
                                        Your application's authorization callback URL(s).
                                    </span>

                                    <button class="btn btn-success" @click.stop.prevent="addRedirectUrl(createForm)">Add Redirect URL</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                        <button type="button" class="btn btn-primary" @click="store">
                            Create
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Client Modal -->
        <div class="modal fade" id="modal-edit-client" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>

                        <h4 class="modal-title">
                            Edit Client
                        </h4>
                    </div>

                    <div class="modal-body">
                        <!-- Form Errors -->
                        <div class="alert alert-danger" v-if="editForm.errors.length > 0">
                            <p><strong>Whoops!</strong> Something went wrong!</p>
                            <br>
                            <ul>
                                <li v-for="error in editForm.errors">
                                    {{ error }}
                                </li>
                            </ul>
                        </div>

                        <!-- Edit Client Form -->
                        <form class="form-horizontal" role="form">
                            <!-- Name -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">Name</label>

                                <div class="col-md-7">
                                    <input id="edit-client-name" type="text" class="form-control"
                                                                @keyup.enter="update" v-model="editForm.fields.name">

                                    <div class="alert alert-danger" v-if="editForm.errors.errors && editForm.errors.errors.name">
                                        <ul>
                                            <li v-for="error in editForm.errors.errors.name">
                                                {{ error }}
                                            </li>
                                        </ul>
                                    </div>

                                    <span class="help-block">
                                        Something your users will recognize and trust.
                                    </span>
                                </div>
                            </div>

                            <!-- Redirect URLs -->
                            <div class="form-group">
                                <label class="col-md-3 control-label">Redirect URL(s)</label>

                                <div class="col-md-7 redirect-urls">
                                    <div class="redirect-url" v-for="(url, index) in editForm.fields.redirect">
                                        <div class="input-group">
                                            <input type="text" class="form-control"
                                                            @keyup.enter="store" v-model="editForm.fields.redirect[index]"/>

                                            <div class="input-group-btn" v-if="index > 0">
                                                <button class="btn btn-danger" 
                                                            @click.stop.prevent="removeRedirectUrl(editForm, index)">X</button>
                                            </div>
                                        </div>

                                        <div class="alert alert-danger" v-if="editForm.errors.errors && editForm.errors.errors['redirect.'+index]">
                                            <ul>
                                                <li v-for="error in editForm.errors.errors['redirect.'+index]">
                                                    {{ error }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>

                                    <span class="help-block">
                                        Your application's authorization callback URL(s).
                                    </span>

                                    <button class="btn btn-success" @click.stop.prevent="addRedirectUrl(editForm)">Add Redirect URL</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Actions -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>

                        <button type="button" class="btn btn-primary" @click="update">
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        /*
         * The component's data.
         */
        data() {
            return {
                clients: [],

                createForm: {
                    errors: [],
                    fields: {
                        name: '',
                        redirect: ['']
                    }
                },

                editForm: {
                    id: '',
                    errors: [],
                    fields: {
                        name: '',
                        redirect: ['']
                    }
                }
            };
        },

        /**
         * Prepare the component (Vue 1.x).
         */
        ready() {
            this.prepareComponent();
        },

        /**
         * Prepare the component (Vue 2.x).
         */
        mounted() {
            this.prepareComponent();
        },

        methods: {
            /**
             * Prepare the component.
             */
            prepareComponent() {
                this.getClients();

                $('#modal-create-client').on('shown.bs.modal', () => {
                    $('#create-client-name').focus();
                });

                $('#modal-edit-client').on('shown.bs.modal', () => {
                    $('#edit-client-name').focus();
                });
            },

            /**
             * Add redirect url to form
             */
            addRedirectUrl(form) {
                form.fields.redirect.push('');
            },

            /**
             * Remove specified redirect url from form
             */
            removeRedirectUrl(form, index) {
                if (index > 0) {
                    form.fields.redirect.splice(index, 1);
                }
            },

            /**
             * Get all of the OAuth clients for the user.
             */
            getClients() {
                axios.get('/oauth/clients')
                        .then(response => {
                            this.clients = response.data;
                        });
            },

            /**
             * Show the form for creating new clients.
             */
            showCreateClientForm() {
                $('#modal-create-client').modal('show');
            },

            /**
             * Create a new OAuth client for the user.
             */
            store() {
                this.persistClient(
                    'post', '/oauth/clients',
                    this.createForm, '#modal-create-client'
                );
            },

            /**
             * Edit the given client.
             */
            edit(client) {
                this.editForm.id = client.id;
                this.editForm.fields.name = client.name;
                this.editForm.fields.redirect = client.redirect;

                $('#modal-edit-client').modal('show');
            },

            /**
             * Update the client being edited.
             */
            update() {
                this.persistClient(
                    'put', '/oauth/clients/' + this.editForm.id,
                    this.editForm, '#modal-edit-client'
                );
            },

            /**
             * Persist the client to storage using the given form.
             */
            persistClient(method, uri, form, modal) {
                form.errors = [];

                axios[method](uri, form.fields)
                    .then(response => {
                        this.getClients();

                        form.fields.name = '';
                        form.fields.redirect = [''];
                        form.errors = [];
                        if (form.id) {
                            form.id = '';
                        }

                        $(modal).modal('hide');
                    })
                    .catch(error => {
                        if (typeof error.response.data === 'object') {
                            form.errors = error.response.data;
                        } else {
                            form.errors = {'message': 'Something went wrong. Please try again.'};
                        }
                    });
            },

            /**
             * Destroy the given client.
             */
            destroy(client) {
                axios.delete('/oauth/clients/' + client.id)
                        .then(response => {
                            this.getClients();
                        });
            }
        }
    }
</script>
