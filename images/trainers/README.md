# Trainer photographs

Empty on purpose. Neither trainer has sent a photograph yet, and until one
arrives their card shows their initials on a brand-coloured tile — which looks
deliberate rather than broken.

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
