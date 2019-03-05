<?php $this->layout( 'layouts/ixpv4' ) ?>

<?php $this->section( 'page-header-preamble' ) ?>
    Reset Password
<?php $this->append() ?>


<?php $this->section( 'page-header-postamble' ) ?>

<?php $this->append() ?>


<?php $this->section( 'content' ) ?>
    <div class="row">
        <div class="col-lg-12">

            <?= $t->alerts() ?>

            <div class="text-center">
                <?php if( config( "identity.biglogo" ) ) :?>
                    <img class="img-fluid" src="<?= config( "identity.biglogo" ) ?>" />
                <?php else: ?>
                    <h2>
                        [Your Logo Here]
                    </h2>
                    <div>
                        Configure <code>IDENTITY_BIGLOGO</code> in <code>.env</code>.
                    </div>
                <?php endif; ?>

            </div>

            <div class="row">
                <div class="col-lg-8 mt-4 mx-auto text-center">

                    <p class="mb-4">
                        Please enter your username and we will send you a password reset token by email.
                    </p>

                    <?= Former::open()->method( 'POST' )
                        ->action( route( 'forgot-password@reset-email' ) )
                        ->customInputWidthClass( 'col-sm-auto col-md-auto col-lg-auto' )
                        ->customLabelWidthClass( 'col-lg-4 col-md-4 col-sm-4 text-sm-right' )
                        ->actionButtonsCustomClass( 'text-center col-sm-12 col-md-12 col-lg-12' )

                    ?>


                    <?= Former::text( 'username' )
                        ->label( 'Username' )
                        ->required()
                        ->blockHelp( '' )
                    ?>

                    <?= Former::actions( Former::primary_submit( 'Reset Password' )->class( "mt-2" ),
                        '<a href="' . route( "login@showForm" ) . '"  class="btn btn-secondary mt-2">Return to Login</a>'
                    );?>



                    <p class="mt-4">
                        For help please contact <a href="<?= route( 'public-content', [ 'page' => 'support' ] ) ?>"><?= config( "identity.name" ) ?></a>
                    </p>

                    <?= Former::close() ?>
                </div>
            </div>

        </div>

    </div>



<?php $this->append() ?>

<?php $this->section( 'scripts' ) ?>

<?php $this->append() ?>