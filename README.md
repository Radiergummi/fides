> **Note: This project has moved to [@zen-trust](https://github.com/zen-trust/app).**
> The newer project also uses a brand-new, Node.js-based tech stack.

---------

Fides
=====
Fides is an SSH certificate signing server. It enables zero-trust infrastructure for your engineers by dynamically, and
transparently, issuing short-lived certificates with clearly defined permissions.

> Fides is in active development and not yet complete. Want to help out? Contributions welcome. Let's make enterprise 
> SSH authorization a commodity, together.

How it works
------------
Fides provides your servers with a trusted CA certificate, and signs your OpenSSH certificates (generated
by `ssh-keygen`) with the same key. As someone attempts to log into a server, sshd will check the signed OpenSSH
certificate and authorize the user.  
As signatures are checked against the public key of your CA, this works without copying your public key around - even
for servers you have never dialed into before!

**Why use Fides?**  
You could certainly just sign the certificates yourself, set them to never expire (or maybe a year), hand them out to
your coworkers, and call it a day. [It's no magic][redhat_ssh_signing]! What Fides enables you to do instead is
dynamically generating signatures for a limited period of time, with a scoped set of privileges, all bound to your
corporate accounts.

**How to authorize**
Instead of SSH-ing directly into servers, you'll need to sign in to the `fides` command-line application first. It
performs an OAuth device authorization, requests a certificate, and starts SSH with the proper parameters. This may look
like so:

```bash
fides ssh some.host.tld

# Fides forwards all arguments to ssh
fides ssh -o ForwardAgent=yes some.host.tld
```

The `ssh` sub-command is optional: You can even symlink `ssh` to the fides executable, and continue to use it as a
stand-in, with all the same options being forwarded.

Requirements
------------
Fides requires a database to run; it understands everything there's a DBAL driver for, but you'll probably want to use
SQLite, PostgreSQL, or MySQL. 

Installation
------------
Set up the project using composer:

```bash
composer create-project radiergummi/fides
```

During the setup, a CA certificate pair will be automatically generated. Make sure to keep these files secret!

Usage
-----
After initializing Fides, you should create an initial admin account:

```bash
php bin/console user:add email@domain.tld --role=ROLE_ADMIN [--password=<SECURE PASSWORD>]
```

You may use this account to sign in to the web interface and configure Fides.

> **Note:**  
> Fides doesn't require you to use local accounts -- in fact, it discourages it. Instead, you should connect it to your
> existing account provider using OAuth federation. Fides includes pre-configured connectors for common providers like
> Microsoft 365, Google Business, GitHub or Okta, but also allows you to set up any other OAuth-enabled provider.

**TODO: Continue documentation**

[redhat_ssh_signing]: https://access.redhat.com/documentation/en-us/red_hat_enterprise_linux/6/html/deployment_guide/sec-creating_ssh_ca_certificate_signing-keys
