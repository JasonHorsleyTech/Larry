# Base

A frontend component that allows "WalkieTalkie" style conversations with GPT.

Some backend classes that easily facilitate customizing how that conversation works.

## Milestones
- [ ] M1 - Basic usage
- [ ] M2 - Functions
- [ ] M3?

### M1 - Basic Usage

- [X] BaseChatPrompt
  - [X] addSystemMessage
  - [X] addUserMessage
- [ ] BaseChatController
  - [-] Models
  - [X] Everything on a job
  - [X] LongPoll for update
  - [ ] invoke without conversation creates and returns
  - [ ] invoke with appends old exchanges
- [ ] Larry.vue
  - [X] Designed
  - [-] Functional: Need to support conversation, not just exchange
  - [ ] Packaged

```php
// Add a custom prompt.
class FredChatPrompt extends BaseChatPrompt
{
    public function __construct()
    {
        $this->addSystemMessage("You are a helpful chatbot named Fred. Answer questions normally, but the first sentence of any response should subtly remind the user that *your name is Fred*.");
    }
}

// Add a Chat controller
class FredChatController extends BaseChatController
{
    public function getPrompt(): BaseChatPrompt
    {
        return new FredChatPrompt('chat');
    }
}

// Invoke on both routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/converse', [FredChatController::class, '__invoke']);
    Route::post('/converse/{conversation}/exchange', [FredChatController::class, '__invoke']);
});

```vue
<script>
// Add from package.
import Larry from "larry"
</script>

<template>
   <Larry path="api/converse" />
</template>
```

<!-- ----------------------------------------------------------------------- -->

### M2
Functions
- [ ] BaseExposedFunction
  - [ ] Good structure. Easy for devs to write, but structured such that GPT gets solid description.
  - [ ] Error messaging *before* GPT calls it. (Test when exposed, not when called)
- [ ] BaseExposedFunctionParam
  - [ ] Required
  - [ ] Optional
- [ ] PromptResponse methods 
  - [ ] requiresBackendFunctionExecution
  - [ ] runFunctionAndUpdateChat
  - [ ] requiresBackendReprompt
- BaseChatPrompt->hasFunctions() -- should be inferred by exposing a function

```php
class FredChatPrompt extends BaseChatPrompt
{
    public function __construct()
    {
        $this->addSystemMessage("You are a helpful chatbot named Fred. Answer questions normally, but the first sentence of any response should subtly remind the user that *your name is Fred*.");
        $this->exposeFunction(CheckTime::class):
    }
}

class CheckTime extends BaseExposedFunction
{
    public static string $name = 'check_time';

    public static string $description = 'Check the current time';

    public static array $params = [       
        'timezone' => new OptionalFunctionParam(
            'string',
            'Specific timezone',
            ['UTC', 'ET'],
        )
    ];

    private string | null $timezone = null;

    public function execute(): string
    {
        return ($this->$timezone) ?
            now()->setTimezone($this->timezone)->toString() :
            now()->toString();
    }
}
```

### M3
Fancy functions
- [ ] FormFunctions
- [ ] 

```php
class TodoChatPrompt extends BaseChatPrompt
{
    public function __construct()
    {
        $this->addSystemMessage("You are a todo-bot. You can create and delete todos for an end user. Todos are visible to end user as soon as you create them, and disappear as soon as you delete them.");
        $this->exposeFunction(CreateTodo::class);
        $this->exposeFunction(DeleteTodo::class);
    }
}

class CreateTodo extends BaseExposedFormFunction {
    public static string $name = 'create_todo';

    public static string $description = 'Create a todo for the user';

    public static array $params = [       
        'timezone' => new OptionalFunctionParam(
            'string',
            'Specific timezone',
            ['UTC', 'ET'],
        )
    ];

    public function getForm(): FormRequest {
        return new CreateTodoRequest();
    }

    public function __construct() {
        
    }

    public function execute(array $validated): string
    {
        $todo = Todo::create($validated);

        return json_encode([
            'id' => $todo->id,
            'content' => $todo->content,
            'due' => $todo->due ?? null,
        ])
    }

}
```
