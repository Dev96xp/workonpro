<?php

return [

    'nav' => [
        'my_projects' => 'My projects',
        'login' => 'Log in',
        'logout' => 'Log out',
    ],

    'register' => [
        'tagline' => 'Free for homeowners',
        'heading' => 'Post your project',
        'subheading' => 'Create your account and hear back from contractors near you.',
        'submit' => 'Create my account',
        'submitting' => 'Creating…',
        'have_account' => 'Already have an account?',
    ],

    'login' => [
        'heading' => 'Welcome back',
        'subheading' => 'Log in to see your projects.',
        'remember' => 'Remember me',
        'submit' => 'Log in',
        'no_account' => "Don't have an account?",
        'create_account' => 'Sign up for free',
    ],

    'fields' => [
        'name' => 'Your name',
        'email' => 'Email',
        'phone' => 'Phone',
        'phone_hint' => 'Only contractors you accept will see it.',
        'city' => 'City',
        'state' => 'State',
        'zip' => 'ZIP code',
        'password' => 'Password',
        'password_confirmation' => 'Confirm your password',
        'title' => 'What do you need?',
        'title_placeholder' => 'E.g. Replace the roof of my house',
        'subcategory' => 'Type of work',
        'subcategory_placeholder' => 'Choose a category',
        'description' => 'Describe your project',
        'description_placeholder' => 'Tell contractors what needs to be done, sizes, materials, etc.',
        'urgency' => 'When do you need it?',
        'budget' => 'Approximate budget',
        'budget_placeholder' => 'Choose a range',
        'optional' => 'optional',
        'location' => 'Location',
    ],

    'dashboard' => [
        'greeting' => 'Hi, :name',
        'heading' => 'My projects',
        'new_project' => 'Post a project',
        'empty_title' => "You don't have any projects yet",
        'empty_body' => 'Post what you need and contractors near you will reach out.',
        'interested_count' => '{0} No interested contractors yet|{1} 1 interested contractor|[2,*] :count interested contractors',
        'new_count' => ':count new|:count new',
    ],

    'form' => [
        'back' => 'Back',
        'create_heading' => 'Post your project',
        'create_subheading' => "It's free. Only contractors you accept will see your contact details.",
        'edit_heading' => 'Edit project',
        'publish' => 'Post project',
        'save' => 'Save changes',
        'created' => "Your project is live! We'll let you know when a contractor is interested.",
        'updated' => 'Project updated.',
    ],

    'show' => [
        'edit' => 'Edit',
        'mark_in_progress' => 'Mark in progress',
        'mark_closed' => 'Close project',
        'close_confirm' => 'Close this project? Contractors will no longer see it.',
        'reopen' => 'Reopen',
        'hidden_notice' => 'This project was hidden by the Workon team. Contact us if you think this is a mistake.',
        'interested_heading' => 'Interested contractors',
        'no_interests' => "No interested contractors yet. We'll email you when someone responds.",
        'view_page' => 'View their page',
        'accept' => 'Accept',
        'decline' => 'Decline',
        'accepted_hint' => 'They can now see your contact details.',
    ],

    'status' => [
        'open' => 'Open',
        'in_progress' => 'In progress',
        'closed' => 'Closed',
    ],

    'urgency' => [
        'urgent' => 'Urgent',
        'this_month' => 'This month',
        'flexible' => 'Just getting quotes',
    ],

    'budget' => [
        'under_1k' => 'Under $1,000',
        '1k_5k' => '$1,000 – $5,000',
        '5k_15k' => '$5,000 – $15,000',
        'over_15k' => 'Over $15,000',
        'not_sure' => 'Not sure',
    ],

    'interest_status' => [
        'pending' => 'New',
        'accepted' => 'Accepted',
        'declined' => 'Declined',
    ],

];
