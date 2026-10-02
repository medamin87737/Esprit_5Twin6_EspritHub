@php($typeActeur = $typeActeur ?? null)

<x-admin.form.input name="libelle" label="Libellé" :value="$typeActeur?->libelle" required
                    placeholder="Ex. Transformateur" maxlength="100" />

<x-admin.form.input name="role_chaine" label="Rôle dans la chaîne" :value="$typeActeur?->role_chaine" required
                    placeholder="Ex. Transforme les matières premières en produits finis" />

<x-admin.form.textarea name="description" label="Description" :value="$typeActeur?->description" rows="4"
                       placeholder="Précisez les missions de ce type d'acteur" help="Facultatif." />
