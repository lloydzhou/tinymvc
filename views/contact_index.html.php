                <h2>Contact List (<?php echo $count; ?> contacts)</h2>        <ul>            <?php foreach ($contact_list as $contact): ?>                <li>                    <strong><?php echo $contact->name ?></strong>                    <a href="mailto:<?php echo $contact->email; ?>"><?php echo $contact->email; ?></a>					<a href="/contact/update/id/<?php echo $contact->id?>">EDIT</a>					<a href="/contact/delete/id/<?php echo $contact->id?>">DELETE</a>                </li>            <?php endforeach; ?>        </ul>		<h2>Create Contact</h2>		You can go to Contact create page to <a href="/contact/create"><b>create</b></a> a new Contact.