<form action="{{ url('secured_login_check') }}" method="post">
    {{ error }}
    <input type="text" name="username"  />
    <input type="password" name="password" value="" />
    <input type="submit" />
</form>
