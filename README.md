# Validate

Adds an AutoValidation trait to your project.
If you use that trait on your models, it will automatically vaildate it by your DB scheme.
Or if there is a `rules` method on your model it will validate the each model by 
the validation rules returned by that method.