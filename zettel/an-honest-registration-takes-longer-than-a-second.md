# An honest registration takes longer than a second

The honeypot's signed timestamp is valid from one second after the form
was rendered. A submission that arrives sooner is rejected.

Proven by hand: posting the real form fields immediately was refused and
logged; the same post after a 2.5-second pause created the user and
redirected to the panel.

A person filling in a name, an address and two passwords cannot do it in
under a second. A script can, and does.

The rejection is logged on purpose. A honeypot that starts firing on real
people is a bug, and without the log nobody would ever find out — the
visitor just sees the form again.

Related: [[a-honeypot-instead-of-a-captcha]]
