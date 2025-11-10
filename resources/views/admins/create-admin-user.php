 <?php require_once base_path('app/helpers/CSRF.php'); ?>
 <a href="/admins/create" class="btn btn-back">Create an Admin</a>
 <div><a href="/admins" class="btn btn-back"> <i class="fa fa-arrow-left" aria-hidden="true"></i>
     Back to List of Admins</a>
 </div>
 <div class="hero-section">
   <h1>Admin Account Access</h1>
   <div class="description">
     <p> When setting up a user account for someone, please create them as an admin first. </p>
   </div>
 </div>
 <div class="form-wrapper">
   <form method="POST" action="/admin/users/store" class="form-container">
     <input type="hidden" name="csrf_token" value="<?= CSRF::generate(); ?>">
     <input type="hidden" name="role" value="admin">
     <input type="hidden" name="id" value="id">
     <div class="form-group">
       <label>First Name:</label>
       <input type="text" name="firstName" value="<?php echo htmlspecialchars($admin[0]['firstName']); ?>" required>
     </div>
     <div class="form-group">
       <label>Last Name:</label>
       <input type="text" name="lastName" value="<?php echo htmlspecialchars($admin[0]['lastName']); ?>" required>
     </div>
     <div class=" form-group">
       <label>Email:</label>
       <input type="email" name="email" value="<?php echo htmlspecialchars($admin[0]['email']); ?>" required>
     </div>
     <div class="form-group">
       <label>Password:</label>
       <input type="password" name="password" required>
     </div>
     <div class="form-group">
       <button type="submit" class="btn btn-add">Submit Admin User Login</button>
     </div>
   </form>
 </div>