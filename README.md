# Symfony DTO Bundle

## Motivation
* Create and deploy a lib to packagist 
* Create a bundle for Symfony
* Facilitate the way of handle serializing

## Usage

Extends your Dto class from `Bigyohann\DtoBundle\Dto\Dto`, 
make all your properties private and add getter. 

By default, I add a convert function to automatically set Dto properties to 
object passed as parameter.

You can annotate your property with attribute
`Bigyohann\DtoBundle\Attributes\ConvertProperty` and if parameter 
`shouldConvertAutomatically` is set at false, property will not be
mapped to object passed as parameter, but you can still access it in Dto if you want to 
do a specific action with this value.


if you don't want to use convert function, you can use
`Bigyohann\DtoBundle\Dto\DtoInterface`.

## Exemple 
```php
use Bigyohann\DtoBundle\Attributes\ConvertProperty;
use Bigyohann\DtoBundle\Dto\Dto;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Type;

class UserDto extends Dto
{
    #[ConvertProperty]
    #[Type(type: 'string')]
    #[Length(min: 2, max: 20)]
    private ?string $name;    
    
    #[ConvertProperty(shouldConvertAutomatically: false)]
    #[Type(type: 'string')]
    private ?string $password;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

}
```
Inject Dto directly in Controller functions
```php
    public function create(UserDto $dto){
        $user = new User();
        
        $dto->transformToObject($user);
        
        // password is not automatically convert in $user, do your custom logic with $dto->getPassword()
        // Add your custom logic here
    }
```
