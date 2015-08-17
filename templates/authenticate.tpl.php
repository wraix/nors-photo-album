<style>
.avatar {
  background-image: url('<?php e($albumIcon); ?>')
}
.form-group {
  margin-bottom: 0px;
}
</style>

<?php $fieldsInError = []; ?>

<div class="row">
  <div class="login-container">
    <div class="avatar"></div>
    <div class="form-box">
      <form action="<?php e($url); ?>" method="post">

      <!-- Display error messages -->
      <?php if (!empty($errors)) : ?>
        <div class="alert alert-danger" role="alert">
        <?php foreach ($errors as $fieldName => $error) : ?>
          <?php if ( !empty($fieldName) ) : ?>
            <?php $fieldsInError[] = $fieldName; ?>
         <?php endif; ?>
          <?php e($error); ?>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php foreach ($fields as $field) : ?>

        <div class="form-group <?php if (in_array($field['name'], $fieldsInError)) { e("has-error"); } ?> has-feedback">
          <input type="<?php e($field['type']); ?>" class="form-control" name="<?php e($field['name']); ?>" id="<?php e($field['id']); ?>" aria-describedby="<?php e($field['id']); ?>_status" placeholder="<?php e($field['placeholder']); ?>" value="<?php e( ($field['retain'] && isset($posted[$field['name']])) ? $posted[$field['name']] : ""); ?>"  <?php e( isset($field['autocomplete']) ? "autocomplete=".$field['autocomplete']."" : ""); ?> <?php e(isset($field['required']) ? "required" : ""); ?> <?php e( isset($field['maxlength']) ? "maxlength=".$field['maxlength']."" : ""); ?> />

        <?php if (in_array($field['name'], $fieldsInError)) : ?>
          <span class="glyphicon glyphicon-remove form-control-feedback" aria-hidden="true"></span>
        <?php endif; ?>

        </div>

      <?php endforeach; ?>

        <button class="btn btn-primary btn-block login" type="submit">Login</button>
      </form>
    </div>
  </div>
</div>