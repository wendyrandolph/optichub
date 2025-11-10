<div><a href="/admins" class="btn btn-back"> <i class="fa fa-arrow-left" aria-hidden="true"></i>
    Back to List of Admins</a>
</div>
<div class="form-wrapper">
  <form method="POST" action="/admins/create" class="form-container">
    <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">
    <h2 class="form-heading"> Add an Admin </h2>
    <div class="form-group">
      <label>Name *</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email">
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="phone">
    </div>
    <div class="form-group">
      <label>Title</label>
      <input type="text" name="title">
    </div>
    <div class="form-group">
      <label>Notes</label>
      <textarea name="notes"></textarea>
    </div>
    <div class="form-group">
      <button type="submit" class="btn btn-save">Save</button>
    </div>

  </form>
</div>