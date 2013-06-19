<form action="{{ url('login') }}" method="post">
    {{ error }}
    <input type="text" name="username"  />
    <input type="password" name="password" value="" />
    <input type="submit" />
</form>
