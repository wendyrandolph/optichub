 <div style="margin-bottom: 1rem;">
   <a href=" /contacts" class="btn btn-back">Back to Contacts</a>
 </div>
 <div class="form-wrapper">

   <form method="POST" action="/admins/store-client-user" class="form-container">
     <input type="hidden" name="csrf_token" value="<?= CSRF::generate() ?>">

     <h2 class="form-heading">Create Client Login</h2>

     <div class="form-group">
       <label>Client Record:</label>
       <select name="client_id" required>
         <option value="">Select Client</option>
         <?php foreach ($clients as $client): ?>
           <option value="<?= $client['id'] ?>">
             <?= htmlspecialchars($client['client_name']) ?>
           </option>
         <?php endforeach; ?>
       </select>
     </div>

     <div class="form-group">
       <label>Full Name:</label>
       <input type="text" name="name" required>
     </div>

     <div class="form-group">
       <label>Email (used to log in):</label>
       <input type="email" name="email" required>
     </div>

     <div class="form-group">
       <label>Temporary Password:</label>
       <input type="text" name="password" required>
     </div>

     <button class="btn btn-add centered-block" type="submit">Create User</button>
   </form>
 </div>
 </div>