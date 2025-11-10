<h2>Create New Proposal</h2>
<p>Fill out the details below based on your conversations with the client.</p>

<form action="/proposals/generate" method="POST">
  <label for="client_name">Client Name:</label>
  <input type="text" id="client_name" name="client_name" required>

  <label for="project_description">Project Description:</label>
  <textarea id="project_description" name="project_description" rows="5" required></textarea>

  <button type="submit">Generate Proposal</button>
</form>