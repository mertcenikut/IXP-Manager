<script>
    $(document).ready( function() {

        $( '.table' ).on( 'click', '.usr-list-delete', function( e ){
            event.preventDefault();

            let objectId = $( "#" + this.id ).attr( "data-object-id" );

            let html = `<form id="d2f-form-delete" method="POST" action="<?= route('user@delete' ) ?>">
                                <div>Do you really want to delete this user?</div>
                                <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="id" value="${objectId}">
                                <input type="hidden" name="redirect-to" value="${objectId}">
                            </form>`;


            bootbox.dialog({
                title: "Delete User",
                message: html,
                buttons: {
                    cancel: {
                        label: 'Close',
                        className: 'btn-secondary',
                        callback: function () {
                            $('.bootbox.modal').modal('hide');
                            return false;
                        }
                    },
                    submit: {
                        label: 'Delete',
                        className: 'btn-danger',
                        callback: function () {
                            $('#d2f-form-delete').submit();
                        }
                    },
                }
            });

        });



    });
</script>