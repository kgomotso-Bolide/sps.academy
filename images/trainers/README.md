# Trainer photographs

`tarryn-norris.jpg`, `fiston-nselike.jpg` and `taryn-mccormick.jpg` are in, all
cleared for use 25 Aug 2026 — Taryn McCormick's covering both her name and that
specific photograph.
Both are 400x400, which is under the 600px asked for below but comfortably enough
for a card whose image column is 180px wide.

Fiston’s is the photograph HE supplied. He was asked for his LinkedIn one and
replied asking to send a different one instead — so this is that one, and his
LinkedIn is still not to be used for anything.

Anyone without a file here falls back to their initials on a brand-coloured tile,
which looks deliberate rather than broken.

## Adding one

1. Save the file here as `firstname-surname.jpg`, lowercase, hyphenated —
   `tarryn-norris.jpg`. Same convention as `images/graduates/`.
2. Add the path to that person's entry in `trainers.js`:

   ```js
   photo: 'images/trainers/tarryn-norris.jpg',
   ```

3. `trainers.js` is on the sync manifest, so that one edit reaches every
   academy. **The image file is not** — copy it into `images/trainers/` in each
   repo, or four sites will ask for a picture that is not there. A missing file
   falls back to the initials rather than showing a broken image, so this fails
   softly, but it still fails.

## What to ask for

Portrait orientation, head and shoulders, at least 600px on the short edge. The
card crops to a tall rectangle, so a wide group photo will lose most of the
person. Ask whether they are happy for that specific photograph to be published,
not just for "a photo" — it is their face on five public websites.


## A photograph for somebody who has not agreed yet

It does not go in this folder. Put it in `private/trainers/` and leave `photo`
empty, so the card falls back to their initials.

Everything in `images/` is uploaded by the deploy, whether or not a page links
it — unlinked is not unpublished. The alternative is a `RewriteRule` in
`.htaccess` denying that one file, which is what Taryn McCormick's photograph
had until she cleared it, but that rule has to name the file, which puts their
name in a `.htaccess` sitting on four public servers, and it is a rule somebody
has to remember to delete later. `private/` is excluded from the deploy mirror
outright, so there is nothing to remember and nothing to leak.

Move the file in here in the same edit that sets `consent: true`. That is the
route `irisha-luhanga.jpg` took on 3 Sep 2026 — and back out again on
10 Sep 2026, when Kgomotso held her back pending the contract. Her file is in
`private/trainers/` now, and `_drafts/trainer-irisha-luhanga.md` has the record
and the steps to put her back.

Her picture had already been deployed by then, and the mirror does not delete,
so `.htaccess` now carries a WHITELIST of the trainer photographs that may be
served rather than a rule naming her. The note beside that rule explains why
that way round, and it is the rule to add a name to when somebody is cleared.

## Ask about the photograph you were actually sent

A file arriving is not clearance, and the file you have may not be the one they
would choose. Check the photograph against any they have published themselves:
if their own bio or profile deck carries a different picture, you have one they
did not send you, and Fiston's case is the reason that matters — he was asked
for his LinkedIn photograph and replied, to everyone, asking to send a different
one instead.
